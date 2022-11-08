<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1;

use Http\Client\HttpClient;
use Http\Message\MessageFactory as MessageFactoryInterface;
use Hydrawiki\Reverb\Client\V1\Api\Api;
use Hydrawiki\Reverb\Client\V1\Api\MessageFactory;
use Hydrawiki\Reverb\Client\V1\Hydrators\ResourceHydrator;
use Hydrawiki\Reverb\Client\V1\Hydrators\ResourceFactory;

class ClientFactory
{
    /**
     * Resource types and their companion Resource.
     *
     * @var \Hydrawiki\Reverb\Client\V1\Resources\Resource[]
     */
    protected $resources = [
        'notifications' => \Hydrawiki\Reverb\Client\V1\Resources\Notification::class,
        'notification-broadcasts' => \Hydrawiki\Reverb\Client\V1\Resources\NotificationBroadcast::class,
        'notification-dismissals' => \Hydrawiki\Reverb\Client\V1\Resources\NotificationDismissals::class,
        'notification-targets' => \Hydrawiki\Reverb\Client\V1\Resources\NotificationTarget::class,
        'sites' => \Hydrawiki\Reverb\Client\V1\Resources\Site::class,
        'users' => \Hydrawiki\Reverb\Client\V1\Resources\User::class,
    ];

    /**
     * Make a new Hydraulics Client using the HTTP Client, message factory and
     * endpoint provided by the package user.
     *
     * @param \Http\Client\HttpClient      $http
     * @param \Http\Message\MessageFactory $messageFactory
     * @param string                       $endpoint
     *
     * @return \Hydrawiki\Reverb\Client\V1\Client
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
