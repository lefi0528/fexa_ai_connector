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
{* `nofilter` is REQUIRED: PrestaShop's Smarty auto-escapes every {$var} to htmlall by
   default, which would turn the JSON-LD quotes into &quot; and break the JSON for crawlers
   (Google/AI can't parse it). The fexajsonld modifier already neutralises the only breakout
   vector (</ -> <\/), so the raw output is safe. *}
<script type="application/ld+json">{$fexa_block|fexajsonld nofilter}</script>
{/foreach}
