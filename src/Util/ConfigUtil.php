<?php

/**
 * @file
 * Contains \Gush\Util\ConfigUtil.
 */

namespace Gush\Util;

class ConfigUtil {

  public static function generateConfigurationIdentifier($adapter, array $adapterConfig) {
    return $adapter . ':' . substr(sha1(serialize($adapterConfig)), 0, 8);
  }

}
