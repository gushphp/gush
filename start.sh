#!/usr/bin/env bash

chown -rf root:root ~/.ssh/config
chmod -rf 644 ~/.ssh/config

/usr/src/gush/gush "$@"
