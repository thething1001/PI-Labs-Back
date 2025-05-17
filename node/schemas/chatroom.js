const mongoose = require("mongoose");

const chatroomSchema = new mongoose.Schema({
  participants: [
    { id: String, first_name: String, last_name: String, status: Boolean },
  ],
});

module.exports = chatroomSchema;
