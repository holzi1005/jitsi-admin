import {io} from "socket.io/client-dist/socket.io";
import {setStatus, showOnlineUsers} from "./onlineStatus";
import {getCookie} from "./cookie";

export var socket = null;
export var token = null;


export function initWebsocket(jwt) {
    token = jwt;
    socket = io('ws://localhost:3000', {
        query: {token}
    });

    socket.on('connect', function (data) {
        setStatus();
    });
    socket.on('sendOnlineUser', function (data) {
        showOnlineUsers(JSON.parse(data))
    })
}

export function sendViaWebsocket(event, message) {
    socket.emit(event, message);
}
