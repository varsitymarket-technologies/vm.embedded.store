# AI Static Website Builder

A dependency-light PHP backend that uses Google AI Studio's Gemini API or OpenAI's API to run a small team of specialist agents and generate a static Tailwind website in `website/index.html`.

## Requirements

- PHP 8.1+
- PHP cURL extension
- A Google AI Studio API key or OpenAI API key

## Setup

1. Copy `.env.example` to `.env`.
2. Choose `AI_PROVIDER=google` or `AI_PROVIDER=openai`.
3. Set `GEMINI_API_KEY` from <https://aistudio.google.com/app/apikey> or `OPENAI_API_KEY` from <https://platform.openai.com/api-keys>.
4. Set `GEMINI_MODEL` or `OPENAI_MODEL` for the selected provider. The default OpenAI model is `gpt-4.1-mini`.

### Docker terminal workflow

Use the included PHP CLI container for terminal commands without installing PHP locally:

```sh
docker compose build

docker compose run --rm php php bin/build.php --brief="A calm, editorial landing page for a neighborhood ceramics studio with class bookings"
```

For a dry-run preview without calling the Gemini API:

```sh
docker compose run --rm php php bin/build.php --brief="A landing page for a solar installation company" --dry-run
```

To start the local web server from inside the container:

```sh
docker compose run --rm -p 8080:8080 php php -S 0.0.0.0:8080 -t public
```

### Agent skill files

Each agent can load a Markdown guide from a skills directory. Place files named after the agent in a folder such as `skills/` and they will act like a system-level instruction guide.

Examples:

```text
skills/
  brand-strategist.md
  experience-designer.md
  copywriter.md
  frontend-engineer.md
```

The file contents are appended as the agent's custom instruction guide. This is useful for downloaded playbooks, internal docs, or reusable operating rules.

```sh
mkdir -p skills
cat > skills/brand-strategist.md <<'EOF'
# Brand strategist guide

- Lead with clarity and trust.
- Emphasize a strong value proposition.
- Keep the call to action specific.
EOF
```

No Composer packages are required. The included `composer.json` only provides project metadata and optional PSR-4 autoload configuration.

## Generate from the CLI

```sh
php bin/build.php --brief="A calm, editorial landing page for a neighborhood ceramics studio with class bookings"
```

The generated page is written to `website/index.html`.

To verify the full pipeline without making an API request:

```sh
php bin/build.php --brief="A landing page for a solar installation company" --dry-run
```

## Run the HTTP service

Point PHP's built-in server at the `public` directory:

```sh
php -S localhost:8080 -t public
```

Send a build request:

```sh
curl -X POST http://localhost:8080 \
  -H 'Content-Type: application/json' \
  -d '{"brief":"A bold landing page for an independent audio equipment maker"}'
```

Use `"dry_run": true` in the JSON body to generate a deterministic local preview without an API key.

## Agent pipeline

1. **Brand strategist** defines audience, positioning, structure, and conversion goal.
2. **Experience designer** defines the responsive visual system and Tailwind layout.
3. **Copywriter** produces page copy and calls to action.
4. **Frontend engineer** turns the brief and team context into one complete HTML5 document.

All API calls are made server-side. The API key is never sent to the generated website. The control panel's AI Agent settings can store either provider key encrypted per store and select which provider powers both the admin chat and website builder.
