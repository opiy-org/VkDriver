<?php
/*
 * Botman.io VkontakteDriver
 * opiy 2017
 * license: freebsd
 *
 */

namespace App\Services\Chat\ChatDrivers\Vkontakte;

use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class VkontaktePhotoDriver extends VkontakteDriver
{

    const DRIVER_NAME = 'VkontaktePhoto';

    public function matchesRequest()
    {
        $check = (array_get($this->myData, 'type', null) == 'message_new');

        if ($check) {
            $audio = false;
            $attachs = array_get($this->myData, 'object.attachments', null);
            foreach ($attachs as $attach) {
                if (array_get($attach, 'type') == 'photo') {
                    $audio = true;
                    break;
                }
            }

            if (!$audio) $check = false;
        }
        // This method detects if the incoming HTTP request should be handled with this driver class.
        return $check;
    }


    /**
     * @return bool
     */
    public function hasMatchingEvent()
    {
        return false;
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = [];
        $attachs = array_get($this->myData, 'object.attachments', null);
        $sender = array_get($this->myData, 'object.user_id');
        $recipient = array_get($this->myData, 'group_id');

        foreach ($attachs as $attach) {
            if (array_get($attach, 'type', null) == 'photo') {
                $message = new IncomingMessage(Image::PATTERN, $sender, $recipient, $this->event);
                $message->setImages([new Image(array_get($attach, 'photo.photo_1280'), array_get($attach, 'photo'))]);
                $messages[] = $message;
            }
        }

        if (count($messages) === 0) {
            return [new IncomingMessage('', '', '')];
        }

        return $messages;
    }


    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
