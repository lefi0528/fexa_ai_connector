{*
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI.
 *
 * @author    Fexa AI <support@fexaai.com>
 * @copyright 2025 Fexa AI
 * @license   Proprietary
 *}
{foreach from=$fexa_jsonld_blocks item=fexa_block}
<script type="application/ld+json">{$fexa_block nofilter}</script>
{/foreach}
