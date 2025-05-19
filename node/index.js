const express = require("express");
const http = require("http");
const socketIo = require("socket.io");
const mongoose = require("mongoose");
const cors = require("cors");
const jwt = require("jsonwebtoken");
const port = 3000;

const messageSchema = require("./schemas/message.js");
const chatroomSchema = require("./schemas/chatroom.js");

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: "http://127.0.0.1:5500",
    methods: ["GET", "POST"],
  },
});

const PHP_API_URL = "http://cms.local";
const MONGO_DB_URL = "mongodb://localhost:27017/cms";
const JWT_SECRET = "supersecret";

// Enable CORS for Express
app.use(
  cors({
    origin: "http://127.0.0.1:5500",
    methods: ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    allowedHeaders: ["Content-Type", "Authorization"],
    credentials: true,
  })
);

// MongoDB connection
mongoose.connect(MONGO_DB_URL);

// MongoDB Schemas
const Message = mongoose.model("Message", messageSchema);
const Chatroom = mongoose.model("Chatroom", chatroomSchema);

app.use(express.json());

// Helper function for HTTP requests to PHP backend
function makeHttpRequest(options, data) {
  return new Promise((resolve, reject) => {
    const req = http.request(options, (res) => {
      let body = "";
      res.setEncoding("utf8");
      res.on("data", (chunk) => {
        body += chunk;
      });
      res.on("end", () => {
        try {
          const parsed = JSON.parse(body);
          resolve({ status: res.statusCode, data: parsed });
        } catch (error) {
          reject(new Error("Invalid JSON response"));
        }
      });
    });

    req.on("error", (error) => {
      reject(error);
    });

    if (data) {
      req.write(JSON.stringify(data));
    }
    req.end();
  });
}

// Middleware to verify JWT
const verifyToken = async (req, res, next) => {
  const token = req.headers.authorization?.replace("Bearer ", "");
  if (!token) return res.status(401).json({ message: "No token provided" });

  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    const url = new URL(`${PHP_API_URL}/students/${decoded.sub}`);
    const options = {
      hostname: url.hostname,
      port: url.port,
      path: url.pathname,
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
      },
    };
    const response = await makeHttpRequest(options);
    if (response.status !== 200) throw new Error("Failed to fetch user");
    req.user = response.data;
    next();
  } catch (error) {
    res.status(401).json({ message: "Invalid token", error: error });
  }
};

// Get all chatrooms
app.get("/chatrooms", verifyToken, async (req, res) => {
  const chatrooms = await Chatroom.find({ "participants.id": req.user.id });
  res.json(chatrooms);
});

app.get("/chatrooms/:id", verifyToken, async (req, res) => {
  const chatroom = await Chatroom.findById(req.params.id);
  res.json(chatroom);
});

app.delete("/chatrooms/:id", verifyToken, async (req, res) => {
  try {
    await Chatroom.findByIdAndDelete(req.params.id);
    await Message.deleteMany({ chatroomId: req.params.id });
    res.status(200).json({ message: "Chatroom deleted successfully" });
  } catch (error) {
    console.error("Error deleting chatroom:", error);
    res.status(500).json({ error: "Internal server error" });
  }
});

// Create chatroom
app.post("/chatrooms", verifyToken, async (req, res) => {
  const { name, participantIds } = req.body;
  const participants = [];
  for (const id of participantIds) {
    const url = new URL(`${PHP_API_URL}/students/${id}`);
    const options = {
      hostname: url.hostname,
      port: url.port,
      path: url.pathname,
      method: "GET",
      headers: {
        Authorization: req.headers.authorization,
        "Content-Type": "application/json",
      },
    };
    const response = await makeHttpRequest(options);
    if (response.status !== 200)
      throw new Error(`Failed to fetch student ${id}: ${response.status}`);
    participants.push({ ...response.data, status: false });
  }
  const chatroom = new Chatroom({ name, participants });
  await chatroom.save();
  io.emit("chatroom_updated");
  res.status(201).json(chatroom);
});

// Add participants to chatroom
app.post("/chatrooms/:id/participants", verifyToken, async (req, res) => {
  const { name, participantIds } = req.body;
  const chatroom = await Chatroom.findById(req.params.id);
  chatroom.participants = [];
  chatroom.name = name;
  for (const id of participantIds) {
    const url = new URL(`${PHP_API_URL}/students/${id}`);
    const options = {
      hostname: url.hostname,
      port: url.port,
      path: url.pathname,
      method: "GET",
      headers: {
        Authorization: req.headers.authorization,
        "Content-Type": "application/json",
      },
    };
    const response = await makeHttpRequest(options);
    if (response.status !== 200)
      throw new Error(`Failed to fetch student ${id}`);
    chatroom.participants.push({ ...response.data, status: false });
  }
  await chatroom.save();
  io.emit("chatroom_updated");
  res.json(chatroom);
});

// Get messages for a chatroom
app.get("/chatrooms/:id/messages", verifyToken, async (req, res) => {
  const messages = await Message.find({ chatroomId: req.params.id }).sort({
    timestamp: 1,
  });
  res.json(messages);
});

// Send message
app.post("/chatrooms/:id/messages", verifyToken, async (req, res) => {
  const { content } = req.body;
  const chatroomId = req.params.id;

  const chatroom = await Chatroom.findById(chatroomId);
  if (!chatroom) {
    return res.status(404).json({ error: "Chatroom not found" });
  }

  const message = new Message({
    chatroomId: chatroomId,
    sender: {
      id: req.user.id,
      first_name: req.user.first_name,
      last_name: req.user.last_name,
    },
    content,
  });
  await message.save();

  chatroom.participants.forEach((participant) => {
    io.to(Number(participant.id)).emit("message", message);
  });

  res.status(201).json(message);
});

// Socket.IO connection
io.on("connection", (socket) => {
  socket.on("join", async ({ userId, token }) => {
    try {
      const decoded = jwt.verify(token, JWT_SECRET);
      socket.join(userId);
      socket.userId = userId;
      await Chatroom.updateMany(
        { "participants.id": userId },
        { $set: { "participants.$.status": true } }
      );
      io.emit("chatroom_updated");
    } catch (error) {
      console.error("Socket join error:", error);
    }
  });

  socket.on("disconnect", async () => {
    await Chatroom.updateMany(
      { "participants.id": socket.userId },
      { $set: { "participants.$.status": false } }
    );
    io.emit("chatroom_updated");
  });
});

server.listen(port, () => {
  console.log(`Server running on port ${port}`);
});
