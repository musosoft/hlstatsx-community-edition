#!/bin/bash
set -euo pipefail
# HLstatsX Community Edition - Real-time player and clan rankings and statistics
# Copyleft (L) 2008-20XX Nicholas Hastings (nshastings@gmail.com)
# http://www.hlxcommunity.com
#
# HLstatsX Community Edition is a continuation of
# ELstatsNEO - Real-time player and clan rankings and statistics
# Copyleft (L) 2008-20XX Malte Bayer (steam@neo-soft.org)
# http://ovrsized.neo-soft.org/
#
# ELstatsNEO is an very improved & enhanced - so called Ultra-Humongus Edition of HLstatsX
# HLstatsX - Real-time player and clan rankings and statistics for Half-Life 2
# http://www.hlstatsx.com/
# Copyright (C) 2005-2007 Tobias Oetzel (Tobi@hlstatsx.com)
#
# HLstatsX is an enhanced version of HLstats made by Simon Garner
# HLstats - Real-time player and clan rankings and statistics for Half-Life
# http://sourceforge.net/projects/hlstats/
# Copyright (C) 2001  Simon Garner
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
# For support and installation notes visit http://www.hlxcommunity.com

if [ -f /root/.maxmind.env ]; then
  # shellcheck disable=SC1091
  . /root/.maxmind.env
fi

API_KEY="${MAXMIND_LICENSE_KEY:-${MAXMIND_API_KEY:-${1:-}}}"

# ***** NOTHING TO CONFIGURE BELOW HERE *****

FILE=GeoLite2-City
FILE_EXT=.tar.gz

# Change to directory where installer is
cd "$(dirname "$0")"

if [ -z "$API_KEY" ] || [[ $API_KEY =~ "<YOUR_API_KEY>" ]]; then
  echo "----------------------------------------------------------"
  echo "[!] You probably forgot to set your MaxMind license key."
  echo "[i] Set MAXMIND_LICENSE_KEY in the environment, pass it as the first argument,"
  echo "[i] or create /root/.maxmind.env with MAXMIND_LICENSE_KEY=..."
  echo "[i] Please check installation instructions > 2.4. Prepare GeoIP2 (optional) > https://github.com/NomisCZ/hlstatsx-community-edition/wiki/Installation#2-installation"
  echo "----------------------------------------------------------"
  exit 3
fi

API_URL="https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=$API_KEY&suffix=tar.gz"
TMPDIR="$(mktemp -d)"
trap 'rm -rf "$TMPDIR"' EXIT

echo "[>>] Downloading GeoLite2-City database"
curl -fL -sS "$API_URL" -o "$TMPDIR/$FILE$FILE_EXT"

echo "[<<] Uncompressing $FILE$FILE_EXT"
tar -zxf "$TMPDIR/$FILE$FILE_EXT" -C "$TMPDIR"

MMDB_PATH="$(find "$TMPDIR" -type f -name "$FILE.mmdb" -print -quit)"
if [ -z "$MMDB_PATH" ]; then
  echo "[!] $FILE.mmdb was not found in the downloaded archive."
  exit 1
fi

if [ ! -s "$MMDB_PATH" ]; then
  echo "[!] Downloaded $FILE.mmdb is empty."
  exit 1
fi

echo "[->] Moving $FILE.mmdb file to $PWD"
install -m 0644 "$MMDB_PATH" "./$FILE.mmdb"

echo "[✓] Done"
