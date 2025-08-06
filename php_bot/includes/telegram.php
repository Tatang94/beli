<?php
/**
 * Telegram API Helper Class
 */

class TelegramAPI {
    private $token;
    
    public function __construct($token) {
        $this->token = $token;
    }
    
    // Send message to Telegram
    public function sendMessage($chat_id, $text, $reply_markup = null) {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }
        
        return $this->makeRequest($url, $data);
    }
    
    // Edit message
    public function editMessage($chat_id, $message_id, $text, $reply_markup = null) {
        $url = "https://api.telegram.org/bot{$this->token}/editMessageText";
        $data = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }
        
        return $this->makeRequest($url, $data);
    }
    
    // Delete message
    public function deleteMessage($chat_id, $message_id) {
        $url = "https://api.telegram.org/bot{$this->token}/deleteMessage";
        $data = [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    // Send photo
    public function sendPhoto($chat_id, $photo, $caption = null, $reply_markup = null) {
        $url = "https://api.telegram.org/bot{$this->token}/sendPhoto";
        $data = [
            'chat_id' => $chat_id,
            'photo' => $photo
        ];
        
        if ($caption) {
            $data['caption'] = $caption;
            $data['parse_mode'] = 'HTML';
        }
        
        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }
        
        return $this->makeRequest($url, $data);
    }
    
    // Answer callback query
    public function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
        $url = "https://api.telegram.org/bot{$this->token}/answerCallbackQuery";
        $data = [
            'callback_query_id' => $callback_query_id,
            'show_alert' => $show_alert
        ];
        
        if ($text) {
            $data['text'] = $text;
        }
        
        return $this->makeRequest($url, $data);
    }
    
    // Set webhook
    public function setWebhook($webhook_url) {
        $url = "https://api.telegram.org/bot{$this->token}/setWebhook";
        $data = [
            'url' => $webhook_url,
            'max_connections' => 100,
            'allowed_updates' => json_encode(['message', 'callback_query'])
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    // Get webhook info
    public function getWebhookInfo() {
        $url = "https://api.telegram.org/bot{$this->token}/getWebhookInfo";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    // Make HTTP request
    private function makeRequest($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return json_decode($result, true);
        } else {
            error_log("Telegram API Error: HTTP $http_code - $result");
            return false;
        }
    }
}
?>