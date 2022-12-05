<?php

declare(strict_types=1);

/*
 * Hivelvet open source platform - https://riadvice.tn/
 *
 * Copyright (c) 2022 RIADVICE SUARL and by respective authors (see below).
 *
 * This program is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Lesser General Public License as published by the Free Software
 * Foundation; either version 3.0 of the License, or (at your option) any later
 * version.
 *
 * Hivelvet is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along
 * with Hivelvet; if not, see <http://www.gnu.org/licenses/>.
 */

namespace Actions\Users;

use Actions\Base as BaseAction;
use Actions\RequirePrivilegeTrait;
use Enum\ResponseCode;
use Models\Role;
use Models\User;
use Respect\Validation\Validator;
use Validation\DataChecker;

/**
 * Class Add.
 */
class Add extends BaseAction
{
    use RequirePrivilegeTrait;

    /**
     * @param \Base $f3
     * @param array $params
     */
    public function save($f3, $params): void
    {
        $body        = $this->getDecodedBody();
        $form        = $body['data'];
        $dataChecker = new DataChecker();

        $dataChecker->verify($form['username'], Validator::length(4)->setName('username'));
        $dataChecker->verify($form['email'], Validator::email()->setName('email'));
        $dataChecker->verify($form['password'], Validator::length(8)->setName('password'));
        $dataChecker->verify($form['role'], Validator::notEmpty()->setName('role'));

        /** @todo : move to locales */
        $errorMessage   = 'User could not be added';
        $successMessage = 'User successfully added';
        if ($dataChecker->allValid()) {
            $user = new User();
            if ($this->credentialsAreValid($form, $user, $errorMessage)) {
                $role = new Role();
                $role->load(['id = ?', [$form['role']]]);
                if ($role->valid()) {
                    $result = $user->saveUserWithDefaultPreset($form['username'], $form['email'], $form['password'], $role->id, $successMessage, $errorMessage);
                    if ($result) {
                        $this->renderJson(['result' => 'success', 'user' => $user->getUserInfos()], ResponseCode::HTTP_CREATED);
                    } else {
                        $this->renderJson(['message' => $errorMessage], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
                    }
                } else {
                    $this->renderJson([], ResponseCode::HTTP_NOT_FOUND);
                }
            }
        } else {
            $this->logger->error($errorMessage, ['errors' => $dataChecker->getErrors()]);
            $this->renderJson(['errors' => $dataChecker->getErrors()], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
