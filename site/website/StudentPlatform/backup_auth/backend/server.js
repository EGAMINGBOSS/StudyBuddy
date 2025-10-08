const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const mysql = require('mysql2/promise');
const cors = require('cors');

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

app.use(cors());
app.use(express.json());

// Database configuration
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'student_platform'
};

// Store active users and their socket connections
const activeUsers = new Map();
const waitingUsers = new Set();

// Socket.io connection handling
io.on('connection', (socket) => {
    console.log('New client connected:', socket.id);
    
    // User joins with their ID
    socket.on('user_join', (userId) => {
        activeUsers.set(userId, socket.id);
        socket.userId = userId;
        console.log(`User ${userId} joined with socket ${socket.id}`);
        
        // Update user online status
        updateUserStatus(userId, true);
    });
    
    // Find random stranger
    socket.on('find_stranger', async (userId) => {
        try {
            const connection = await mysql.createConnection(dbConfig);
            
            // Check for waiting users
            const [waitingSessions] = await connection.execute(
                'SELECT id, session_id, user1_id FROM chat_sessions WHERE status = "waiting" AND user1_id != ? ORDER BY RAND() LIMIT 1',
                [userId]
            );
            
            if (waitingSessions.length > 0) {
                // Match with waiting user
                const session = waitingSessions[0];
                await connection.execute(
                    'UPDATE chat_sessions SET user2_id = ?, status = "active", started_at = NOW() WHERE id = ?',
                    [userId, session.id]
                );
                
                // Notify both users
                const user1Socket = activeUsers.get(session.user1_id);
                const user2Socket = activeUsers.get(userId);
                
                if (user1Socket) {
                    io.to(user1Socket).emit('stranger_found', {
                        sessionId: session.session_id,
                        strangerId: userId
                    });
                }
                
                if (user2Socket) {
                    io.to(user2Socket).emit('stranger_found', {
                        sessionId: session.session_id,
                        strangerId: session.user1_id
                    });
                }
            } else {
                // Create waiting session
                const sessionId = `chat_${Date.now()}_${userId}`;
                await connection.execute(
                    'INSERT INTO chat_sessions (session_id, user1_id, status) VALUES (?, ?, "waiting")',
                    [sessionId, userId]
                );
                
                socket.emit('waiting_for_stranger', { sessionId });
            }
            
            await connection.end();
        } catch (error) {
            console.error('Error finding stranger:', error);
            socket.emit('error', { message: 'Failed to find stranger' });
        }
    });
    
    // Join chat session
    socket.on('join_session', (sessionId) => {
        socket.join(sessionId);
        console.log(`User ${socket.userId} joined session ${sessionId}`);
    });
    
    // Send message
    socket.on('send_message', async (data) => {
        try {
            const { sessionId, senderId, message, messageType } = data;
            const connection = await mysql.createConnection(dbConfig);
            
            // Get session info
            const [sessions] = await connection.execute(
                'SELECT id, user1_id, user2_id FROM chat_sessions WHERE session_id = ?',
                [sessionId]
            );
            
            if (sessions.length > 0) {
                const session = sessions[0];
                
                // Insert message
                const [result] = await connection.execute(
                    'INSERT INTO chat_messages (session_id, sender_id, message, message_type) VALUES (?, ?, ?, ?)',
                    [session.id, senderId, message, messageType || 'text']
                );
                
                // Get sender info
                const [users] = await connection.execute(
                    'SELECT username, profile_picture FROM users WHERE id = ?',
                    [senderId]
                );
                
                const messageData = {
                    id: result.insertId,
                    sessionId,
                    senderId,
                    message,
                    messageType: messageType || 'text',
                    timestamp: new Date().toISOString(),
                    username: users[0].username,
                    profile_picture: users[0].profile_picture
                };
                
                // Broadcast to session
                io.to(sessionId).emit('new_message', messageData);
            }
            
            await connection.end();
        } catch (error) {
            console.error('Error sending message:', error);
            socket.emit('error', { message: 'Failed to send message' });
        }
    });
    
    // User is typing
    socket.on('typing', (data) => {
        socket.to(data.sessionId).emit('user_typing', {
            userId: socket.userId,
            isTyping: data.isTyping
        });
    });
    
    // Shuffle to new stranger
    socket.on('shuffle', async (data) => {
        try {
            const { sessionId, userId } = data;
            const connection = await mysql.createConnection(dbConfig);
            
            // End current session
            await connection.execute(
                'UPDATE chat_sessions SET status = "ended", ended_at = NOW() WHERE session_id = ?',
                [sessionId]
            );
            
            // Notify other user
            socket.to(sessionId).emit('stranger_left');
            socket.leave(sessionId);
            
            await connection.end();
            
            // Find new stranger
            socket.emit('shuffle_complete');
            setTimeout(() => {
                socket.emit('find_stranger', userId);
            }, 500);
        } catch (error) {
            console.error('Error shuffling:', error);
            socket.emit('error', { message: 'Failed to shuffle' });
        }
    });
    
    // End session
    socket.on('end_session', async (sessionId) => {
        try {
            const connection = await mysql.createConnection(dbConfig);
            
            await connection.execute(
                'UPDATE chat_sessions SET status = "ended", ended_at = NOW() WHERE session_id = ?',
                [sessionId]
            );
            
            // Notify other user
            socket.to(sessionId).emit('session_ended');
            socket.leave(sessionId);
            
            await connection.end();
        } catch (error) {
            console.error('Error ending session:', error);
        }
    });
    
    // Disconnect
    socket.on('disconnect', async () => {
        console.log('Client disconnected:', socket.id);
        
        if (socket.userId) {
            activeUsers.delete(socket.userId);
            await updateUserStatus(socket.userId, false);
        }
    });
});

// Update user online status
async function updateUserStatus(userId, isOnline) {
    try {
        const connection = await mysql.createConnection(dbConfig);
        await connection.execute(
            'UPDATE users SET is_online = ?, last_seen = NOW() WHERE id = ?',
            [isOnline, userId]
        );
        await connection.end();
    } catch (error) {
        console.error('Error updating user status:', error);
    }
}

// API endpoints
app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', message: 'WebSocket server is running' });
});

app.get('/api/online-users', (req, res) => {
    res.json({ count: activeUsers.size, users: Array.from(activeUsers.keys()) });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`WebSocket server running on port ${PORT}`);
});