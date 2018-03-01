<?php
/*
 * Botman.io VkontakteDriver
 * opiy 2017
 * license: freebsd
 *
 */

namespace App\Services\Chat\ChatDrivers\Vkontakte;

use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Attachments\Audio;

class VkontakteAudioDriver extends VkontakteDriver
{

    const DRIVER_NAME = 'VkontakteAudio';

    public function matchesRequest()
    {
        $check = (array_get($this->myData, 'type', null) == 'message_new');

        if ($check) {
            $audio = false;
            $attachs = array_get($this->myData, 'object.attachments', null);
            foreach ($attachs as $attach) {
                if (
                    (array_get($attach, 'type', null) == 'audio')
                    or ((array_get($attach, 'type', null) == 'doc') and (array_get($attach, 'doc.title', null) == 'voice_message.webm'))
                ) {
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
            if (array_get($attach, 'type', null) == 'audio') {
                $message = new IncomingMessage(Audio::PATTERN, $sender, $recipient, $this->event);
                $message->setAudio([new Audio(array_get($attach, 'audio.url'), $attach['audio'])]);
                $messages[] = $message;
            } elseif ((array_get($attach, 'type', null) == 'doc') and (array_get($attach, 'doc.title', null) == 'voice_message.webm')) {
                $message = new IncomingMessage(Audio::PATTERN, $sender, $recipient, $this->event);
                $message->setAudio([new Audio(array_get($attach, 'doc.preview.audio_msg.link_ogg'), $attach['doc'])]);
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
