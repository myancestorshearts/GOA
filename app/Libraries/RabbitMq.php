<?php 

namespace App\Libraries;

use App\External\PhpAmqpLib\Connection\AMQPStreamConnection;
use App\External\PhpAmqpLib\Message\AMQPMessage;

class RabbitMq {


    private $connection;
    private $channel;

    function __construct()
    {
        // get the application key and add it to the beginning of the message
        $this->connection = new AMQPStreamConnection('172.31.1.184', 5672, 'goa', 'password');
        $this->channel = $this->connection->channel();
    }

    public function queueMessage($queue, $message)
    {
        $msg = new AMQPMessage(json_encode($message));
        $this->channel->batch_basic_publish($msg, '', $queue);
    }
    public function publishBatch() {
        $this->channel->publish_batch();
    }
    public function get($queue) {
        $message = $this->channel->basic_get($queue);
        return $message;
    }
    public function ack($message) {
        $this->channel->basic_ack($message->getDeliveryTag());
    }
    public function nack($message) {
        $this->channel->basic_nack($message->getDeliveryTag());
    }
}   

