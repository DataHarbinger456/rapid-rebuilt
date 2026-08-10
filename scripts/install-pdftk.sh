#!/usr/bin/env bash
#
# Install a self-contained pdftk into vendor-bin/pdftk/.
#
# Laravel Cloud's production image is Debian bookworm on arm64 and provides no
# way to apt-install system packages outside Private Cloud, so the HSMV form
# generator has no pdftk and every DMV form fails. This script stages a private
# JRE and the pdftk-java jars into the application directory during the build,
# where no root is required and the files persist into the running image.
#
# pdftk-java does publish a dependency-free native binary, but only for x86_64,
# so it cannot be used here. The jar plus a headless JRE is the portable route.
#
# Everything is pinned by version and verified by SHA-256: a build that silently
# pulls a different pdftk would change what is printed on a state form.
#
# Safe to run locally. It detects the host OS and architecture, so a developer
# on an Apple Silicon Mac gets a macOS JRE and the same launcher interface.
#
#   ./scripts/install-pdftk.sh          # install if missing
#   ./scripts/install-pdftk.sh --force  # reinstall from scratch

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_DIR="${PROJECT_ROOT}/vendor-bin/pdftk"
LAUNCHER="${TARGET_DIR}/bin/pdftk"

# Temurin 21 LTS, headless-capable JRE. Checksums are the ones Adoptium
# publishes for these exact assets.
JRE_VERSION="21.0.12+8"
JRE_TAG="jdk-21.0.12%2B8"

# Only the two platforms this project actually targets are pinned: Laravel
# Cloud (Linux arm64) and local development (macOS arm64). Adding another
# means fetching its real checksum from the Adoptium API, never guessing one.
JRE_LINUX_ARM64_FILE="OpenJDK21U-jre_aarch64_linux_hotspot_21.0.12_8.tar.gz"
JRE_LINUX_ARM64_SHA="5f9c96b656827b9d14ebeda7739e25be554fa6d25669b03847c1df6e869c0679"

JRE_MAC_ARM64_FILE="OpenJDK21U-jre_aarch64_mac_hotspot_21.0.12_8.tar.gz"
JRE_MAC_ARM64_SHA="36bb71d6fa5184e12a6483e7662783c2cbd383f5dca8034140f0a84dd5aa797d"

# The upstream "all" jar, which already bundles BouncyCastle and
# commons-lang3. Deliberately preferred over Debian's pdftk-java package: that
# ships the bare jar and declares two separate dependency packages, so it would
# mean three .deb downloads plus `ar` to unpack them, and `ar` is not
# guaranteed to exist in a PHP build image. One self-contained jar needs
# nothing but curl.
#
# Architecture-independent, being bytecode.
PDFTK_JAR_URL="https://gitlab.com/api/v4/projects/5024297/packages/generic/pdftk-java/v3.3.3/pdftk-all.jar"
PDFTK_JAR_SHA="a694d49bd03e1edd4c23b3ba808bc221eb8a8ccfe7bfd2a0a884b2b2fb425188"
PDFTK_VERSION="3.3.3"

FORCE=0
[ "${1:-}" = "--force" ] && FORCE=1

log()  { printf '  %s\n' "$*"; }
fail() { printf '  ERROR: %s\n' "$*" >&2; exit 1; }

sha256_of() {
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1" | cut -d' ' -f1
    elif command -v shasum >/dev/null 2>&1; then
        shasum -a 256 "$1" | cut -d' ' -f1
    else
        fail "Neither sha256sum nor shasum is available; cannot verify downloads."
    fi
}

# Downloads to $2 and aborts unless the content matches $3. A mismatch means
# the artefact is not what was pinned, which is a stop-the-build condition
# rather than something to warn about and continue.
fetch_verified() {
    local url="$1" dest="$2" expected="$3" actual

    log "downloading $(basename "$dest")"
    curl -fsSL --retry 3 --retry-delay 2 -o "$dest" "$url" \
        || fail "Could not download ${url}"

    actual="$(sha256_of "$dest")"

    if [ "$actual" != "$expected" ]; then
        fail "Checksum mismatch for $(basename "$dest")
    expected ${expected}
    actual   ${actual}"
    fi
}

detect_platform() {
    local os arch
    os="$(uname -s)"
    arch="$(uname -m)"

    case "${os}/${arch}" in
        Linux/aarch64|Linux/arm64)
            JRE_FILE="$JRE_LINUX_ARM64_FILE"; JRE_SHA="$JRE_LINUX_ARM64_SHA"; JRE_LAYOUT="linux" ;;
        Darwin/arm64)
            JRE_FILE="$JRE_MAC_ARM64_FILE";   JRE_SHA="$JRE_MAC_ARM64_SHA";   JRE_LAYOUT="mac" ;;
        *)
            # Deliberately not falling back to a guess. An unverified JRE on an
            # unexpected platform is how you get a build that "succeeds" and
            # then cannot produce a title application.
            fail "Unsupported platform ${os}/${arch}.
    This script pins a JRE for Linux arm64 (Laravel Cloud) and macOS arm64 (local dev).
    On another platform, install pdftk through your package manager instead;
    the application falls back to a system pdftk automatically." ;;
    esac
}

# =====================
# MAIN
# =====================

detect_platform

if [ -x "$LAUNCHER" ] && [ "$FORCE" -eq 0 ]; then
    if "$LAUNCHER" --version >/dev/null 2>&1; then
        log "pdftk already installed at ${LAUNCHER#"$PROJECT_ROOT"/}"
        log "run with --force to reinstall"
        exit 0
    fi
    log "existing install is not runnable; reinstalling"
fi

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

log "installing pdftk into ${TARGET_DIR#"$PROJECT_ROOT"/} (${JRE_LAYOUT} $(uname -m))"

rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR/bin" "$TARGET_DIR/lib"

# --- JRE ---
fetch_verified \
    "https://github.com/adoptium/temurin21-binaries/releases/download/${JRE_TAG}/${JRE_FILE}" \
    "${TMP_DIR}/jre.tar.gz" \
    "$JRE_SHA"

mkdir -p "${TMP_DIR}/jre"
tar -xzf "${TMP_DIR}/jre.tar.gz" -C "${TMP_DIR}/jre"

# The tarball contains a single versioned directory. macOS builds bury the
# runtime another two levels down inside a bundle.
JRE_ROOT="$(find "${TMP_DIR}/jre" -maxdepth 1 -mindepth 1 -type d -print -quit)"
[ -n "$JRE_ROOT" ] || fail "Unexpected JRE archive layout"

if [ "$JRE_LAYOUT" = "mac" ]; then
    JRE_ROOT="${JRE_ROOT}/Contents/Home"
fi

[ -x "${JRE_ROOT}/bin/java" ] || fail "No java executable in the extracted JRE"

mv "$JRE_ROOT" "${TARGET_DIR}/jre"

# --- pdftk ---
fetch_verified "$PDFTK_JAR_URL" "${TARGET_DIR}/lib/pdftk-all.jar" "$PDFTK_JAR_SHA"

# --- launcher ---
# Resolves its own location so the tree can be moved, and forces headless mode
# because the build image has no display and the JVM will otherwise warn.
cat > "$LAUNCHER" <<'LAUNCHER_EOF'
#!/usr/bin/env sh
# Generated by scripts/install-pdftk.sh. Do not edit; rerun the script.
HERE="$(cd "$(dirname "$0")/.." && pwd)"
exec "${HERE}/jre/bin/java" \
    -Djava.awt.headless=true \
    -jar "${HERE}/lib/pdftk-all.jar" \
    "$@"
LAUNCHER_EOF

chmod +x "$LAUNCHER"

# --- verify it actually runs ---
if ! VERSION_OUTPUT="$("$LAUNCHER" --version 2>&1 | head -1)"; then
    fail "The launcher was written but does not run:
    ${VERSION_OUTPUT}"
fi

log "installed: ${VERSION_OUTPUT}"
log "launcher:  ${LAUNCHER#"$PROJECT_ROOT"/}"
log "pdftk:     pdftk-java ${PDFTK_VERSION} (all-in-one jar)"
log "jre:       Temurin ${JRE_VERSION}"
