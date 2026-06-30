<?php
/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI.
 *
 * @author    Fexa AI <support@fexaai.com>
 * @copyright 2025 Fexa AI
 * @license   Proprietary
 */

namespace PrestaShop\Module\FexaAiConnector\Controller\Admin;

if (!defined('_PS_VERSION_')) {
    exit;
}

/*
 * Cross-version admin controller base.
 *
 * PrestaShop 9 removed `FrameworkBundleAdminController` (used on 1.7.8 / 8) in
 * favour of `PrestaShopAdminController`. We pick whichever exists so a single
 * codebase runs on 1.7.8, 8.x and 9.x. Both ultimately extend Symfony's
 * AbstractController, so `$this->render()` is available in all cases.
 */
if (class_exists(\PrestaShopBundle\Controller\Admin\PrestaShopAdminController::class)) {
    abstract class AbstractFexaAdminController extends \PrestaShopBundle\Controller\Admin\PrestaShopAdminController
    {
    }
} else {
    abstract class AbstractFexaAdminController extends \PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController
    {
    }
}
