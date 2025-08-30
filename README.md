College Picks WordPress Theme
=============================

This theme provides a PHP-based implementation of the "College Picks" app. It registers two custom post types:

- `game` — stores game entries (home/away/kickoff/week).
- `pick` — stores user picks (one per user per game).

Repository
----------
This local theme can be connected to the GitHub repository:

https://github.com/rayflores/college-picks-wp

Setup (one-time)
----------------
1. From your theme directory, run the included setup script to initialize git and add the remote:

```bash
./setup-git.sh https://github.com/rayflores/college-picks-wp.git
```

That script will initialize a git repo (if needed), create a first commit, add the remote, and push the `main` branch. You will need appropriate credentials (SSH key or PAT) configured for pushes.

CI
--
A GitHub Actions workflow is included at `.github/workflows/ci.yml` to run PHP syntax checks and project phpcs code style (via Composer). The workflow runs automatically on push and PRs.

Local linting
-------------
Install composer dev dependencies and run phpcs locally:

```bash
composer install --dev
composer run phpcs
```

Notes
-----
- The theme is intentionally minimal; feel free to improve styles and templates.
- If you prefer to keep the original React app, I can restore the manifest-based enqueue and add server endpoints instead of converting to PHP templates.
