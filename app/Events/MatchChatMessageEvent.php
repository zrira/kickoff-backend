<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchChatMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Game $match,
        public readonly ChatMessage $chatMessage
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("match.{$this->match->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        $this->chatMessage->loadMissing('user');

        return [
            'id' => $this->chatMessage->id,
            'match_id' => $this->match->id,
            'user' => [
                'id' => $this->chatMessage->user->id,
                'name' => $this->chatMessage->user->name,
                'avatar' => $this->chatMessage->user->avatar,
            ],
            'message' => $this->chatMessage->message,
            'created_at' => $this->chatMessage->created_at->toISOString(),
        ];
    }
}
