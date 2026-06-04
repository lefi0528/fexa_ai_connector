# Compatibilité PrestaShop — Fexa AI Connector

## Matrice cible

| PrestaShop | PHP requis | État |
|------------|-----------|------|
| **1.7.8.x** | **8.1+** | ✅ Activé (plancher de compat abaissé). Nécessite PHP 8.1 sur l'hébergeur. |
| **8.x** | 8.1+ | ✅ Inchangé. |
| **9.x** | 8.1+ | ⚠️ Activé via shim admin — **à valider en conditions réelles** (voir checklist). |

> **Contrainte PHP forte :** le serveur MCP repose sur `php-mcp/server ^3.3` (attributs PHP 8,
> Monolog 3) → **PHP 8.1 minimum sur toutes les versions**, y compris 1.7.8.
> PrestaShop 1.7.0→1.7.7 (PHP 7.x uniquement) ne sont **pas** supportés par ce build.

## Changements apportés (branche `ps17-compat`)

1. `fexa_ai_connector.php` — `ps_versions_compliancy.min` : `8.0.0` → **`1.7.8.0`**
   (débloque l'installation sur PrestaShop 1.7.8).
2. `composer.json` — `config.platform.php` : `7.4` → **`8.1`** (cohérent avec `require: php >=8.1`) ;
   `version` : `3.2.8` → **`3.3.0`** (aligné sur `config.xml` et le module).
3. `src/Controller/Admin/AbstractFexaAdminController.php` (**nouveau**) — classe de base admin
   transverse : utilise `PrestaShopAdminController` (PS 9) ou `FrameworkBundleAdminController`
   (1.7.8 / 8) selon ce qui existe. PS 9 a supprimé `FrameworkBundleAdminController`.
4. `src/Controller/Admin/FexaAiConfigController.php` — `extends AbstractFexaAdminController`
   au lieu de `FrameworkBundleAdminController` (était un fatal sur PS 9).

> Le **cœur MCP** (front controller `McpServer` + `php-mcp/server`) ne dépend d'aucune classe admin :
> il fonctionne sur 1.7.8 / 8 / 9 dès lors que PHP est en 8.1+.

## Checklist de validation par version (à exécuter sur de vraies installs)

### PrestaShop 1.7.8 (PHP 8.1)
- [ ] Installation du module sans erreur (plus de blocage « incompatible »).
- [ ] La page de config admin s'affiche et montre la clé API (`AdminFexaAiConfig`).
- [ ] L'URL MCP répond : `POST /index.php?fc=module&module=fexa_ai_connector&controller=McpServer`
      avec `Authorization: Bearer <FEXA_AI_API_KEY>` → `tools/list` renvoie les 11 outils.
- [ ] Un scan complet depuis le SaaS lit produits/catégories/CMS.
- [ ] Une optimisation appliquée modifie bien la fiche (description/meta) sans casser le thème.

### PrestaShop 8.x (PHP 8.1) — régression
- [ ] Tout ce qui précède fonctionne toujours (comportement inchangé attendu).

### PrestaShop 9.x (PHP 8.1) — **à confirmer**
- [ ] Installation + page de config admin (vérifie le shim `PrestaShopAdminController`).
      Si la page admin échoue : `PrestaShopAdminController` peut exiger une injection de services
      au constructeur → adapter la déclaration de service du contrôleur (autowire).
- [ ] **`src/EventListener/ModuleEventListener.php`** : vérifier que
      `PrestaShopBundle\Event\ModuleManagementEvent` existe toujours en PS 9. S'il a été
      déplacé/renommé, garder l'auto-enregistrement des modules MCP tiers derrière un
      `class_exists()` (l'écouteur n'est pas indispensable au cœur MCP).
- [ ] Endpoint MCP + scan + apply (mêmes tests que 1.7.8).

## Points hors-périmètre de ce lot (backlog connu)
- Support PHP 7.4 (PS 1.7.6/1.7.7) — nécessiterait de retirer `php-mcp/server` et les attributs
  (dispatcher JSON-RPC maison). Non couvert ici (choix : plancher PHP 8.1).
- Sanitisation HTML avant écriture (anti-casse template) — à traiter côté SaaS et/ou module.
