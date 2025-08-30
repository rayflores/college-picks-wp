#!/usr/bin/env bash
# Setup git remote and push to GitHub. Usage: ./setup-git.sh <remote-repo-url>
set -euo pipefail

REPO_URL="${1:-}" 
if [ -z "$REPO_URL" ]; then
  echo "Usage: $0 <remote-repo-url>"
  exit 2
fi

# Ensure we're in theme dir
ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT_DIR"

if [ ! -d .git ]; then
  echo "Initializing git repository..."
  git init
else
  echo "Git repository already initialized."
fi

# Add remote if not present
if git remote get-url origin >/dev/null 2>&1; then
  echo "Remote 'origin' already exists. Skipping add."
else
  git remote add origin "$REPO_URL"
  echo "Added remote origin -> $REPO_URL"
fi

# Create initial commit if there are no commits
if git rev-parse --verify HEAD >/dev/null 2>&1; then
  echo "Repository already has commits."
else
  git add --all
  git commit -m "Initial College Picks theme commit"
  git branch -M main
  echo "Pushing to origin main..."
  git push -u origin main
fi

echo "Setup complete. If push failed, configure your credentials (SSH/PAT) and re-run the push commands shown above."
