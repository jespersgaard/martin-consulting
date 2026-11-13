#!/usr/bin/env bash
# Bygger sitet og pusher dist/ til det offentlige deploy-repo, som Simply.com importerer fra.
set -euo pipefail

REPO="jespersgaard/martin-consulting-site"
cd "$(dirname "$0")/.."

npm run build

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

if gh repo view "$REPO" >/dev/null 2>&1; then
  gh repo clone "$REPO" "$TMP/site" -- -q
else
  gh repo create "$REPO" --public --description "Bygget version af martin-consulting (deploy)" >/dev/null
  git init -q -b main "$TMP/site"
  git -C "$TMP/site" remote add origin "https://github.com/$REPO.git"
fi

find "$TMP/site" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
cp -a dist/. "$TMP/site/"

cd "$TMP/site"
git add -A
if git diff --cached --quiet; then
  echo "Ingen ændringer at deploye."
  exit 0
fi
git commit -q -m "Deploy $(date -u +%Y-%m-%dT%H:%MZ)

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
git push -q -u origin main
echo "Deployet til https://github.com/$REPO"
