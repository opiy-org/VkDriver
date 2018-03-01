<?php
/*
 * Botman.io VkontakteDriver
 * opiy 2017
 * license: freebsd
 *
 */

namespace App\Services\Chat\ChatDrivers\Vkontakte;

use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Attachments\Audio;

class VkontakteFileDriver extends VkontakteDriver
{

    const DRIVER_NAME = 'VkontakteFile';

    public function matchesRequest()
    {
        $check = (array_get($this->myData, 'type', null) == 'message_new');

        if ($check) {
            $file = false;
            $attachs = array_get($this->myData, 'object.attachments', null);
            foreach ($attachs as $attach) {
                if ((array_get($attach, 'type') == 'doc') and (array_get($attach, 'doc.title', null) != 'voice_message.webm')) {
                    $file = true;
                    break;
                }
            }

            if (!$file) $check = false;
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
            if (array_get($attach, 'type', null) == 'doc') {
                $message = new IncomingMessage(File::PATTERN, $sender, $recipient, $this->event);
                $message->setFiles([new File(array_get($attach, 'doc.url'), array_get($attach, 'doc'))]);
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
