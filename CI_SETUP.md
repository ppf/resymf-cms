# CI Quality Automation Setup Guide

## 📋 Overview

This project now includes comprehensive CI/CD quality automation using **GitHub Actions**. The setup includes multiple quality gates to ensure code quality, security, and maintainability.

## 🎯 CI Pipelines

### 1. Main Quality Checks (`.github/workflows/ci-quality.yml`)

**Triggers:**
- Push to `master` or `develop` branches
- Pull requests to `master` or `develop` branches

**Jobs:**

#### **Code Quality & Static Analysis**
- Multi-PHP version testing (5.6, 7.0, 7.1, 7.2, 7.3, 7.4)
- PHPUnit test execution with coverage
- Code coverage upload to Codecov
- Composer package validation

#### **PHPStan Static Analysis**
- Level 5 static analysis on PHP 7.4
- Symfony-specific configuration
- Doctrine ORM support
- Automatic error reporting on GitHub

#### **Code Style Check (PHP-CS-Fixer)**
- PSR-2 and Symfony coding standards
- Automatic configuration generation
- Detailed diff output for violations

#### **Security Vulnerability Scan**
- Composer security audit
- Outdated dependency checks
- Automatic vulnerability reporting

#### **Doctrine Schema Validation**
- MySQL 5.7 service container
- Automatic database setup
- Schema validation and sync checks

#### **Code Metrics & Complexity**
- PHPLOC code metrics analysis
- Complexity measurements
- LOC (Lines of Code) statistics

#### **PHP Lint & Syntax Check**
- Parallel PHP syntax validation
- Multi-version compatibility (5.6-7.4)
- Fast parallel execution

### 2. Security Scanning (`.github/workflows/security-scan.yml`)

**Triggers:**
- Weekly schedule (Mondays at 00:00 UTC)
- Push to `master` or `develop`
- Pull requests
- Manual workflow dispatch

**Jobs:**

#### **Composer Security Audit**
- Composer vulnerability scanning
- JSON report generation
- Critical vulnerability detection

#### **Dependency Vulnerability Check**
- Outdated dependency detection
- Direct dependency analysis
- JSON report with recommendations

#### **OWASP Dependency Check**
- Advanced vulnerability scanning
- Industry-standard OWASP methodology
- HTML report generation

#### **PHP Security Checker**
- Local PHP security scanner
- Known vulnerability database
- JSON report output

#### **Psalm Security Analysis**
- Taint analysis for security issues
- Static security vulnerability detection
- Detailed security report

#### **Security Summary**
- Consolidated security report
- All scan results aggregation
- Artifact preservation

## 📁 Configuration Files

### PHPStan Configuration (`phpstan.neon`)

```yaml
parameters:
    level: 5
    paths:
        - src
    symfony:
        container_xml_path: app/cache/dev/appDevDebugProjectContainer.xml
    excludePaths:
        - src/ReSymf/Bundle/*/Tests/*
```

**Features:**
- Level 5 analysis (balanced strictness)
- Symfony framework integration
- Doctrine ORM support
- Test directory exclusion
- Parallel processing

### PHP-CS-Fixer Configuration (`.php-cs-fixer.php`)

**Rules Applied:**
- PSR-2 and Symfony standards
- Short array syntax
- Ordered imports
- Unused import removal
- Binary operator spacing
- PHPDoc formatting
- Trailing commas in multiline arrays

### PHPUnit Configuration (`phpunit.xml.dist`)

**Features:**
- Bootstrap from `app/autoload.php`
- Coverage whitelist for `src/` directory
- Exclusion of Tests, Resources, DataFixtures
- Multiple output formats (HTML, Clover, JUnit)
- Text coverage output to stdout

## 🚀 Usage

### Running Locally

#### Install Development Dependencies

```bash
# Merge dev dependencies into main composer.json
# Or manually add from composer-dev-requirements.json

composer require --dev phpstan/phpstan \
    phpstan/phpstan-symfony \
    phpstan/phpstan-doctrine \
    friendsofphp/php-cs-fixer \
    vimeo/psalm \
    phploc/phploc
```

#### Run Quality Checks

```bash
# PHPStan static analysis
vendor/bin/phpstan analyse --memory-limit=1G

# PHP-CS-Fixer code style check
vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

# PHP-CS-Fixer auto-fix
vendor/bin/php-cs-fixer fix --verbose

# PHPUnit tests with coverage
vendor/bin/phpunit --coverage-html build/coverage

# Psalm security analysis
vendor/bin/psalm --taint-analysis

# Code metrics
vendor/bin/phploc src --log-json=build/phploc.json

# Composer security audit
composer audit

# Check outdated dependencies
composer outdated --direct
```

#### Use Composer Scripts (after merging composer-dev-requirements.json)

```bash
# Run all quality checks
composer quality-check

# Auto-fix code style issues
composer quality-fix

# Run tests with coverage
composer test-coverage

# Run PHPStan
composer phpstan

# Run Psalm
composer psalm

# Run code metrics
composer phploc
```

### CI/CD Integration

#### GitHub Actions

The workflows are automatically triggered on:
- **Push events** to `master` or `develop`
- **Pull request events** targeting `master` or `develop`
- **Weekly security scans** (Mondays)
- **Manual triggers** (workflow_dispatch)

#### Status Badges

Add to your `README.md`:

```markdown
![CI Quality](https://github.com/YOUR_USERNAME/resymf-cms/workflows/CI%20Quality%20Checks/badge.svg)
![Security Scan](https://github.com/YOUR_USERNAME/resymf-cms/workflows/Security%20Scanning/badge.svg)
[![codecov](https://codecov.io/gh/YOUR_USERNAME/resymf-cms/branch/master/graph/badge.svg)](https://codecov.io/gh/YOUR_USERNAME/resymf-cms)
```

## 📊 Quality Gates

### Code Quality Requirements

✅ **Required for merge:**
- All PHPUnit tests passing
- No PHPStan errors at level 5
- Code style compliant with PSR-2/Symfony standards
- No critical security vulnerabilities
- Code coverage ≥ 70% (recommended)

⚠️ **Warnings (review required):**
- Outdated dependencies
- Medium/low security issues
- High code complexity (cyclomatic complexity > 10)
- Low code coverage (< 70%)

### Security Requirements

✅ **Required:**
- No critical vulnerabilities in Composer audit
- No known CVEs in dependencies
- Psalm taint analysis passing

⚠️ **Review Required:**
- Medium/low severity vulnerabilities
- Outdated dependencies with security patches

## 🔧 Customization

### Adjusting PHPStan Level

Edit `phpstan.neon`:

```yaml
parameters:
    level: 6  # Increase strictness (0-9)
```

### Customizing Code Style Rules

Edit `.php-cs-fixer.php`:

```php
return $config
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        // Add or modify rules here
        'array_syntax' => ['syntax' => 'long'], // Use long array syntax
    ]);
```

### Excluding Files from Analysis

**PHPStan (`phpstan.neon`):**

```yaml
parameters:
    excludePaths:
        - src/Legacy/*
        - src/*/Tests/*
```

**PHP-CS-Fixer (`.php-cs-fixer.php`):**

```php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->exclude('Legacy')
    ->exclude('Tests');
```

### Adjusting Test Coverage Thresholds

Edit `.github/workflows/ci-quality.yml`:

```yaml
- name: Check code coverage
  run: |
    coverage=$(php -r "echo json_decode(file_get_contents('build/logs/clover.xml'))->project->metrics->coveredstatements / json_decode(file_get_contents('build/logs/clover.xml'))->project->metrics->statements * 100;")
    if (( $(echo "$coverage < 70" | bc -l) )); then
      echo "Code coverage is below 70%: $coverage%"
      exit 1
    fi
```

## 📈 Reports & Artifacts

### Available Artifacts

**Code Quality Pipeline:**
- PHPUnit HTML coverage report
- PHPUnit Clover XML
- PHPUnit JUnit XML
- PHPLOC JSON metrics

**Security Pipeline:**
- Composer audit JSON
- Outdated dependencies JSON
- OWASP HTML report
- PHP Security Checker JSON
- Psalm security report
- Security summary markdown

### Accessing Reports

**In GitHub Actions:**
1. Navigate to Actions tab
2. Select workflow run
3. Scroll to "Artifacts" section
4. Download reports

**Locally:**
```bash
# Generate coverage report
vendor/bin/phpunit --coverage-html build/coverage

# Open in browser
open build/coverage/index.html

# Generate metrics
vendor/bin/phploc src --log-json=build/phploc.json
cat build/phploc.json | jq .
```

## 🎓 Best Practices

### Before Committing

1. **Run quality checks locally:**
   ```bash
   composer quality-check
   ```

2. **Fix code style automatically:**
   ```bash
   composer quality-fix
   ```

3. **Check test coverage:**
   ```bash
   composer test-coverage
   ```

4. **Review security issues:**
   ```bash
   composer audit
   ```

### During Development

- Write tests for new features (TDD approach)
- Keep code complexity low (single responsibility)
- Follow PSR-2/Symfony coding standards
- Update dependencies regularly
- Document complex logic with PHPDoc

### Code Review Checklist

- [ ] All CI checks passing
- [ ] No new PHPStan errors introduced
- [ ] Code style compliant
- [ ] Tests added for new features
- [ ] No security vulnerabilities
- [ ] Documentation updated
- [ ] Changelog updated (if applicable)

## 🐛 Troubleshooting

### PHPStan Out of Memory

```bash
# Increase memory limit
vendor/bin/phpstan analyse --memory-limit=2G

# Or edit phpstan.neon:
parameters:
    memory_limit: '2G'
```

### PHP-CS-Fixer Conflicts with Git

```bash
# Add to .gitignore
.php-cs-fixer.cache
```

### Composer Audit Failures

```bash
# Audit with allowed plugins
composer audit --no-dev

# Check specific package
composer why vendor/package
```

### Coverage Not Generating

```bash
# Ensure Xdebug is installed
php -m | grep xdebug

# Or use PCOV (faster)
composer require --dev pcov/clobber
vendor/bin/pcov clobber
```

## 📚 Additional Resources

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHP-CS-Fixer Documentation](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Psalm Documentation](https://psalm.dev/docs/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [OWASP Dependency Check](https://owasp.org/www-project-dependency-check/)

## 🤝 Contributing

When contributing to this project:

1. Ensure all CI checks pass
2. Follow the code style guidelines
3. Write tests for new features
4. Update documentation as needed
5. Keep dependencies up to date
6. Address security vulnerabilities promptly

## 📄 License

This CI configuration is part of the resymf-cms project and follows the same license (MIT).

---

**Last Updated:** 2025-11-11
**Maintained By:** Development Team
**CI Version:** 1.0.0
