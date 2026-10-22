#!/usr/bin/env bash
set -euo pipefail

report_directory="${1:-build/upstream}"
: "${GITHUB_REPOSITORY:?Set GITHUB_REPOSITORY to the issue repository}"
jq -e '(.changed | type) == "boolean"' "$report_directory/report.json" > /dev/null

if ! jq -e '.changed' "$report_directory/report.json" > /dev/null; then
    exit 0
fi

issue_number="$(gh issue list --repo "$GITHUB_REPOSITORY" --state open \
    --limit 1000 --json number,title,body \
    --jq '.[] | select(.title == "Duitku upstream changes" and (.body | contains("<!-- duitku-upstream-watch -->"))) | .number' | head -n 1)"

case "$issue_number" in
    '')
        gh issue create --repo "$GITHUB_REPOSITORY" --title 'Duitku upstream changes' \
            --body-file "$report_directory/report.md"
        ;;
    *[!0-9]*)
        echo 'GitHub returned an unexpected issue number.' >&2
        exit 1
        ;;
    *)
        gh issue edit "$issue_number" --repo "$GITHUB_REPOSITORY" --body-file "$report_directory/report.md"
        ;;
esac
