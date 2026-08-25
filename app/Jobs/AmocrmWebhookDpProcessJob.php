<?php

namespace App\Jobs;

use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\ServiceMessageNote;
use App\Notifications\NotificationProcessor;
use App\Prototypes\AmocrmWebhookSettingsBag;

class AmocrmWebhookDpProcessJob extends BaseWebhookDpProcessJob
{
    public function handle(): int
    {
        WidgetHook::build($this->data)
            ->setSettingsClass(AmocrmWebhookSettingsBag::class)
            ->register(EntityType::Lead(), EventType::Any(), function(WebhookDpDataPrototype $data) {
                if (!KAuth::getInstall()->isActiveStatus()) {
                    KAuth::getApiClient()->notes(EntityTypesInterface::LEADS)->addOne((new ServiceMessageNote())
                        ->setEntityId($data->getEntityId())
                        ->setText("Не отправили оповещение. Виджет имеет некорректный статус")
                    );
                    logger()->debug('У виджета не активный статус');
                    return;
                }
                $accountId = KAuth::getAccount()->getAmocrmId();
                (new NotificationProcessor($this->getEntityId(), $this->getSettings(), $accountId))->handle();
            })
            ->register(EntityType::Customer(), EventType::Any(), function (WebhookDpDataPrototype $data) {
                (new Notes(KAuth::getApiClient()))->addToCustomer($data->getEntityId(),'Интеграция не поддерживает работу с покупателями');
            })
            ->handle();
        return 0;
    }
}
