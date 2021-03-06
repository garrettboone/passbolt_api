<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.13.0
 */
namespace App\Service\Users;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use App\Utility\Healthchecks\AbstractHealthcheckService;
use App\Utility\Healthchecks\Healthcheck;
use App\Utility\OpenPGP\OpenPGPBackend;
use Cake\ORM\TableRegistry;

class UsersHealthcheckService extends AbstractHealthcheckService
{
    const CATEGORY = 'data';
    const NAME = 'Users';
    const CHECK_VALIDATES = 'Can validate';

    /**
     * @var UsersTable
     */
    private $table;

    /**
     * Users Healthcheck constructor.
     *
     * @param OpenPGPBackend $gpg gpg backend to use
     * @param UsersTable $table secret table
     */
    public function __construct($gpg = null, $table = null)
    {
        parent::__construct(self::NAME, self::CATEGORY);
        $this->table = $table ?? TableRegistry::getTableLocator()->get('Users');
        $this->checks[self::CHECK_VALIDATES] = $this->healthcheckFactory(self::CHECK_VALIDATES, true);
    }

    /**
     * @inheritDoc
     */
    public function check()
    {
        $records = $this->table->find()->all();

        foreach ($records as $i => $record) {
            $this->canValidate($record);
        }

        return $this->getHealthchecks();
    }

    /**
     * Validates
     *
     * @param User $user user
     * @return void
     */
    private function canValidate(User $user)
    {
        $copy = $this->table->newEntity($user->toArray());
        $error = $copy->getErrors();

        // Ignore profile
        unset($error['profile']);

        if (count($error)) {
            $this->checks[self::CHECK_VALIDATES]->fail()
            ->addDetail(__('Validation failed for user {0}. {1}', $user->id, json_encode($copy->getErrors())), Healthcheck::STATUS_ERROR);
        } else {
            $this->checks[self::CHECK_VALIDATES]
                ->addDetail(__('Validation success for user {0}', $user->id), Healthcheck::STATUS_SUCCESS);
        }
    }
}
