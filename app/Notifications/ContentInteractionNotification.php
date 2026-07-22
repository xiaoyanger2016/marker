<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Phase 18.4: 内容互动通知
 *  - type: comment / vote / activity_joined / followed
 *  - data: { actor_name, content_title, content_id, url, message }
 */
class ContentInteractionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,    // comment / vote / activity_joined / followed
        public string $title,   // 通知标题
        public string $message, // 详细描述
        public string $url,     // 点击跳转
        public ?int $contentId = null,
        public ?int $activityId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // 暂只走 DB, 不发邮件
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => $this->type,
            'title'      => $this->title,
            'message'    => $this->message,
            'url'        => $this->url,
            'content_id' => $this->contentId,
            'activity_id' => $this->activityId,
        ];
    }
}
