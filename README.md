# ReSymf-CMS

ReSymf-CMS is a Symfony 2.4-based platform that unifies a lightweight CMS, a Scrum-friendly project management tool, and a small CRM into a single responsive web application. It is designed for teams that need to publish marketing pages, keep delivery work on track, and stay close to customers without juggling multiple back-office systems.

## Why ReSymf-CMS?
- Built on the proven Symfony Standard Edition, so you inherit familiar conventions, bundles, and deployment workflows.
- Modular architecture: each bundle (CMS, Project Manager, CRM) can evolve independently yet share the same authentication layer, Doctrine models, and Twig-driven UI components.
- Responsive front-end out of the box, enabling editors and account managers to work comfortably on desktops, tablets, or phones.
- Developer-first: full source code, clean separation of concerns, and Symfony CLI tooling make it easy to extend or integrate with existing services.

## Bundles & Capabilities
### CMS Bundle
- Create and publish pages, articles, or custom objects using Doctrine entities.
- Friendly permalink generation to keep URLs readable and SEO-friendly.
- Twig templating with Assetic for asset management.

### Project Manager Bundle
- Organize projects, sprints, and backlog items aligned with Scrum ceremonies.
- Track progress, assignees, and blocking issues from the same workspace.
- Provides a starting point for burn-down dashboards or integrations with external issue trackers.

### CRM Bundle
- Maintain a lightweight client database, including contacts, meetings, and tasks.
- Generate invoices and keep an eye on employee utilization alongside delivery work.
- Centralizes customer context for both project and commercial teams.

## Tech Stack
- PHP >= 5.3.3
- Symfony 2.4 Standard Edition
- Doctrine ORM & DoctrineBundle
- Twig + Twig Extensions
- Assetic, SwiftMailer, Monolog
- Composer for dependency management

## Getting Started
```bash
git clone git@github.com:<you>/resymf-cms.git
cd resymf-cms
composer install    # installs bundles and builds parameters
cp app/config/parameters.yml.dist app/config/parameters.yml
# update DB credentials, mailer config, etc. inside parameters.yml
php app/console doctrine:schema:update --force
php app/console assetic:dump --env=prod --no-debug
php app/console server:run 0.0.0.0:8000
```

### Common Tasks
- `php app/console cache:clear --env=prod` – warm caches for production deploys.
- `php app/console doctrine:fixtures:load` – load demo data (once fixtures are configured).
- `php app/console resymf:sync --bundle=<name>` – placeholder for your custom sync commands.

## Project Structure Highlights
- `app/` – Symfony configuration, parameters, cache, and console bootstrap.
- `src/` – All project bundles (`CmsBundle`, `ProjectManagerBundle`, `CrmBundle`, etc.).
- `web/` – Public document root served by your HTTP server.
- `docs/` – Additional functional specs and UI mockups (start here if you need context).

## Documentation & Support
- Project site: [resymf-cms.pl](http://resymf-cms.pl)
- Local documentation lives under the `docs/` directory; start with the architecture overview for bundle interactions.
- For issues or feature requests, open a ticket in the tracker associated with this repository.

## License

This project is published under the MIT License. See `LICENSE` for details.
