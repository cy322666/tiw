<?php

namespace App\Services\amoCRM;

use App\Models\Account;
use App\Models\amoCRM\Field;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ufee\Amo\Oauthapi;

class Client
{
    public Oauthapi $service;
    public EloquentStorage $storage;

    public bool $auth = false;
    public bool $logs = false;

    public function __construct(Model $account)
    {
        $this->storage = new EloquentStorage([
            'domain'    => $account->subdomain,
            'client_id' => $account->client_id,
            'client_secret' => $account->client_secret,
            'redirect_uri'  => $account->redirect_uri,
        ], $account);

        Oauthapi::setOauthStorage($this->storage);
    }

    /**
     * @throws Exception
     */
    public function init(): Client
    {
        if (!$this->storage->model->subdomain) {

            return $this;
        }

        $this->service = Oauthapi::setInstance([
            'domain'        => $this->storage->model->subdomain,
            'client_id'     => $this->storage->model->client_id,
            'client_secret' => $this->storage->model->client_secret,
            'redirect_uri'  => $this->storage->model->redirect_uri,
        ]);

        try {
            $this->service->account;

            $this->auth = true;

        } catch (Exception $exception) {

//            Log::error(__METHOD__, [$exception->getMessage().' '.$exception->getFile().' '.$exception->getLine()]);
            if ($this->storage->model->refresh_token) {

                $oauth = $this->service->refreshAccessToken($this->storage->model->refresh_token);
            } else
                $oauth = $this->service->fetchAccessToken($this->storage->model->code);

            $this->storage->setOauthData($this->service, [
                'token_type'    => 'Bearer',
                'expires_in'    => $oauth['expires_in'],
                'access_token'  => $oauth['access_token'],
                'refresh_token' => $oauth['refresh_token'],
                'created_at'    => $oauth['created_at'] ?? time(),
            ]);

            $this->auth = true;
        }

        $this->service->queries->setDelay(1);

        return $this;
    }

    public function initCache(int $time = 3600) : Client
    {
        \Ufee\Amo\Services\Account::setCacheTime($time);

        return $this;
    }

    public function initLogs(): Client
    {
        $this->service->queries->onResponseCode(429, function(\Ufee\Amo\Base\Models\QueryModel $query) {
            \App\Models\Log::query()->create([
                'code' => 429,
                'url'  => $query->getUrl(),
                'method'  => $query->method,
                'details' => json_encode($query->toArray()),
            ]);
        });
        $this->service->queries->listen(function(\Ufee\Amo\Base\Models\QueryModel $query) {

            $log = \App\Models\Log::query()->create([
                'code'  => $query->response->getCode(),
                'url'   => $query->getUrl(),
                'start' => $query->startDate(),
                'end'   => $query->endDate(),
                'method'  => $query->method,
                'details' => json_encode($query->toArray()),
            ]);

//            print_r($query->headers);
            if ($query->response->getCode() === 0) {

                $log->error = $query->response->getError();
            } else
                $log->data = strlen($query->response->getData() > 1) ? $query->response->getData() : [];

            $log->save();
        });

        return $this;
    }
}
