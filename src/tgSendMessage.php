<?php

function tgSendMessage($text) {
    get_file(TG_API . TG_TOKEN . "/sendMessage",
            array("chat_id" => TG_CHAT_ID,
                "message_thread_id" => TG_THREAD_ID,
                "text" => $text));
}
