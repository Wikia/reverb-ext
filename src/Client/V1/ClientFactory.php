<?php

declare(strict_types=1);

namespace Reverb\Client\V1;

use Http\Client\HttpClient;
use Http\Message\MessageFactory as MessageFactoryInterface;
use Reverb\Client\V1\Api\Api;
use Reverb\Client\V1\Api\MessageFactory;
use Reverb\Client\V1\Hydrators\ResourceFactory;
use Reverb\Client\V1\Hydrators\ResourceHydrator;

class ClientFactory
{
    /**
     * Resource types and their companion Resource.
     *
     * @var \Reverb\Client\V1\Resources\Resource[]
     */
    protected $resources = [
        'notifications' => \Reverb\Client\V1\Resources\Notification::class,
        'notification-broadcasts' => \Reverb\Client\V1\Resources\NotificationBroadcast::class,
        'notification-dismissals' => \Reverb\Client\V1\Resources\NotificationDismissals::class,
        'notification-targets' => \Reverb\Client\V1\Resources\NotificationTarget::class,
        'sites' => \Reverb\Client\V1\Resources\Site::class,
        'users' => \Reverb\Client\V1\Resources\User::class,
    ];

    /**
     * Make a new Hydraulics Client using the HTTP Client, message factory and
     * endpoint provided by the package user.
     *
     * @param \Http\Client\HttpClient      $http
     * @param \Http\Message\MessageFactory $messageFactory
     * @param string                       $endpoint
     *
     * @return \Reverb\Client\V1\Client
     */
    public function make(
        HttpClient $http,
        MessageFactoryInterface $messageFactory,
        string $endpoint,
        string $apiKey
    ): Client {
        $api = new Api(
            $http,
            new MessageFactory($messageFactory, $endpoint, ['Authorization' => $apiKey])
        );

        $hydrator = new ResourceHydrator(
            new ResourceFactory($this->resources)
        );

        return new Client($api, $hydrator);
    }
}
