const mongoose = require("mongoose");

const chatroomSchema = new mongoose.Schema({
  name: String,
  participants: [
    { id: String, first_name: String, last_name: String, status: Boolean },
  ],
});

module.exports = chatroomSchema;
