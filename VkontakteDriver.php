<?php
/*
 * Botman.io VkontakteDriver
 * opiy 2017
 * license: freebsd
 *
 */

namespace App\Services\Chat\ChatDrivers;

use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class VkontakteDriver extends Driver
{


    protected $vkontakteProfileEndpoint = 'https://api.vk.com/method/users.get?v=5.0&user_ids=';

    const DRIVER_NAME = 'Vkontakte';
    protected $myData = [];

    /**
     * @param Request $request
     * @return void
     */
    public function buildPayload(Request $request)
    {
        // This method receives the incoming HTTP Request and allows you
        // to read the driver relevant information from it.
        $this->myData = $request->request->all();
        //   \Log::debug('Driver buildPayload: mydata' . print_r($this->myData, true));
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $check = isset($this->myData['object']) && isset($this->myData['type']) && ($this->myData['type'] == 'message_new');
        // This method detects if the incoming HTTP request should be handled with this driver class.
        return $check;

    }


    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        $message = [new Message($this->myData['object']['body'], $this->myData['group_id'], $this->myData['object']['user_id'])];
        // \Log::debug('Driver getMessages: ' . print_r($message, true));
        // Return the message(s) that are inside the incoming request.
        return $message;
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }


    /**
     * @param Message $matchingMessage
     *
     * @return Answer
     */
    public function getConversationAnswer(Message $matchingMessage)
    {
        // Return the given answer, when inside a conversation.
        return Answer::create($matchingMessage->getMessage());
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        // Send a reply to the messaging service.
        // Replies can either be strings, Question objects or IncomingMessage objects.
        if ($message instanceof Question) {
            $additionalParameters['message'] = $message->getText();
        } elseif ($message instanceof IncomingMessage) {
            $additionalParameters['message'] = $message->getMessage();
        } else {
            $additionalParameters['message'] = $message;
        }

        $additionalParameters['user_id'] = $matchingMessage->getChannel();
        $additionalParameters['access_token'] = $this->config->get('vkontakte_token');
        $this->http->get('https://api.vk.com/method/messages.send', $additionalParameters);
    }


    /**
     * @param Message $matchingMessage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function types(Message $matchingMessage)
    {
        $parameters = [
            'user_id' => $matchingMessage->getChannel(),
            'access_token' => $this->config->get('vkontakte_token'),
            'type' => 'typing',
            'v' => '5.38',
        ];

        return $this->http->get('https://api.vk.com/method/messages.setActivity', $parameters);
    }


    /**
     * Return the driver name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Vkontakte';
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return !is_null($this->config->get('vkontakte_token'));
    }

    /**
     * Retrieve User information.
     * ++
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        $profileData = $this->http->get($this->vkontakteProfileEndpoint . $matchingMessage->getChannel());
        $profileData = json_decode($profileData->getContent())->response[0];

        // \Log::debug('VK getUser' . print_r($profileData, true));

        $firstName = isset($profileData->first_name) ? $profileData->first_name : null;
        $lastName = isset($profileData->last_name) ? $profileData->last_name : null;
        $uname = strlen(trim($firstName . $lastName)) > 0 ? trim($firstName . $lastName) : $profileData->id;

        return new User($matchingMessage->getChannel(), $firstName, $lastName, $uname);
    }

    /**
     * Low-level method to perform driver specific API requests.
     * ++
     * @param string $endpoint
     * @param array $parameters
     * @param Message $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, Message $matchingMessage)
    {

        $parameters = array_replace_recursive([
            'access_token' => $this->config->get('vkontakte_token'),
            'v' => '5.38',
        ], $parameters);

        $request = $this->http->get('https://api.vk.com/method/' . $endpoint, $parameters);
        // \Log::debug('VK sendRequest: ' . $endpoint . '  ---- ' . print_r($parameters, true));

        return $request;

    }
}
