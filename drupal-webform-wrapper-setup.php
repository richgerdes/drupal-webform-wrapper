#!/bin/php
<?php

$GIT_REFERENCE = $argv[1] ?? '8.x';
$COMPOSER_JSON_URL = 'https://raw.githubusercontent.com/roygoldman/drupal-webform-wrapper/' . $GIT_REFERENCE . '/composer.json';

function has_composer_repository($local_repo, $remote_url) {
    if (isset($local_repo['repositories'])) {
        foreach ($local_repo['repositories'] as $repository) {
            if (
                $repository['type'] === 'composer'
                && isset($repository['url'])
                && $repository['url'] === $remote_url) {
                return true;
            }
        }
    }
    return false;
}

function has_package_repository($local_repo, $name) {
    if (isset($local_repo['repositories'])) {
        foreach ($local_repo['repositories'] as $key => $repository) {
            if ($repository['type'] === 'package') {
                if (isset($repository['package']['name'])) {
                    if ($repository['package']['name'] === $name) {
                        # Package definition exists.
                        return $key;
                    }
                }
                elseif (is_array($repository['package'])) {
                    # Multiple versions are defined for the package.
                    $package_names = array_column($repository['package'], 'name');
                    if (in_array($name, $package_names)) {
                        # Defined versions are for the target package.
                        return $key;
                    }
                }
            }
        }
    }
    return false;
}

$upstream_composer_config_file = file_get_contents($COMPOSER_JSON_URL);
$upstream_composer_config = json_decode($upstream_composer_config_file, true);
$local_repo = json_decode(file_get_contents('./composer.json'), true);
#var_export($upstream_composer_config);


foreach ($upstream_composer_config['repositories'] as $repository) {
    if ($repository['type'] === 'composer') {
        if (!has_composer_repository($local_repo, $repository['url'])) {
            $local_repo['repositories'][] = $repository;
        }
    }
    elseif ($repository['type'] === 'package') {
        $key = has_package_repository($local_repo, $repository['package']['name']);
        if ($key !== false) {
            $upstream_version = $repository['package']['version'];
            $local_definition = $local_repo['repositories'][$key];
            if (isset($local_definition['package']['name'])) {
                # Simple package definiton. Verify version.
                $local_version = $local_definition['package']['version'];
                if ($local_version !== $upstream_version) {
                    # Upstream has a new version, add it to the list.
                    $local_repo['repositories'][$key]['package'] = [
                        $local_definition['package'],
                        $repository['package'],
                    ];
                }
            }
            else {
                # Mutiple defined versions, verify version doesn't exist.
                $package_versions = array_column($local_definition['package'], 'version');
                if (!in_array($upstream_version, $package_versions)) {
                    # Add repo if version not defined.
                    $local_repo['repositories'][$key]['package'][] = $repository['package'];
                }
            }
        }
        else {
            $local_repo['repositories'][] = $repository;
        }
    }
}

file_put_contents('./composer.json', json_encode($local_repo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, JSON_PRETTY_PRINT);
