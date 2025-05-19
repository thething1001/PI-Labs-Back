const mongoose = require("mongoose");

const messageSchema = new mongoose.Schema({
  chatroomId: String,
  sender: {
    id: String,
    first_name: String,
    last_name: String,
  },
  content: String,
  timestamp: { type: Date, default: Date.now },
});

module.exports = messageSchema;
