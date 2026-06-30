# Compatibilité PrestaShop — Fexa AI Connector

## Matrice cible

| PrestaShop | PHP requis | État |
|------------|-----------|------|
| **8.1+** (série 8) | **8.1+** | ✅ Supporté. |
| **9.x** | 8.1+ | ✅ Supporté (config admin via `Module::getContent()`, sans contrôleur Symfony admin). |

> **PrestaShop 1.7.x et 8.0.x ne sont PAS supportés.** Le serveur MCP repose sur
> `php-mcp/server ^3.3` (attributs PHP 8, Monolog 3) → **PHP 8.1 minimum**. Les boutiques
> 1.7.x (PHP 7.x) et toute install sous PHP < 8.1 ne peuvent pas honorer ce plancher ; on ne
> déclare donc pas une compatibilité qu'on ne peut pas tenir.
> Plancher déclaré dans le code : `ps_versions_compliancy = ['min' => '8.1.0', 'max' => '9.99.99']`.

## Architecture & points de compat clés

- **Page de config admin via `Module::getContent()`** (rendu Smarty `views/templates/admin/configure.tpl`,
  toutes variables échappées) — aucun contrôleur Symfony admin, donc aucune dépendance à
  `FrameworkBundleAdminController` (supprimé en PS 9) ni à `PrestaShopAdminController`. Identique sur 8.1 / 9.
- **Cœur MCP** (front controller `McpServer` + `php-mcp/server`) — indépendant de toute classe admin ;
  fonctionne sur 8.1 / 9 dès que PHP ≥ 8.1. Les classes outils (`src/Mcp/Tools/*`) sont découvertes
  par attributs/réflexion (`CustomDiscoverer`) et instanciées par le registre `php-mcp` — **hors**
  conteneur Symfony PrestaShop. Elles accèdent au contexte boutique via `Context::getContext()`
  (le singleton ambiant), l'usage standard PrestaShop ; il n'y a pas de point d'injection DI ici.
- **Données structurées (JSON-LD)** injectées via `hookDisplayHeader`, en PHP, avec garde anti-breakout
  `</`→`<\/` (un template Smarty imposerait soit de casser le JSON via `escape`, soit un `nofilter`
  signalé par le validateur — cf. note de soumission Marketplace).

## Checklist de validation (sur de vraies installs)

### PrestaShop 8.1.x (PHP 8.1)
- [ ] Installation du module sans erreur.
- [ ] Page de config admin (`getContent`, rendu Smarty) : clé API + cases « données structurées » affichées, enregistrement OK.
- [ ] Endpoint MCP : `POST /index.php?fc=module&module=fexa_ai_connector&controller=McpServer`
      avec `Authorization: Bearer <FEXA_AI_API_KEY>` → `tools/list` renvoie les outils.
- [ ] Scan complet depuis le SaaS (produits / catégories / CMS).
- [ ] Une optimisation appliquée modifie la fiche (description / méta) sans casser le thème.

### PrestaShop 9.x (PHP 8.1)
- [ ] Tout ce qui précède.
- [ ] **`src/EventListener/ModuleEventListener.php`** : vérifier que
      `PrestaShopBundle\Event\ModuleManagementEvent` existe toujours en PS 9 (sinon garder
      l'auto-enregistrement des modules MCP tiers derrière un `class_exists()`, non indispensable au cœur MCP).

## Backlog connu
- Sanitisation HTML avant écriture (anti-casse template) — à traiter côté SaaS et/ou module.
