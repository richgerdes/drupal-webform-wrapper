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
                        // Package definition exists.
                        return $key;
                    }
                }
                elseif (is_array($repository['package'])) {
                    // Multiple versions are defined for the package.
                    $package_names = array_column($repository['package'], 'name');
                    if (in_array($name, $package_names)) {
                        // Defined versions are for the target package.
                        return $key;
                    }
                }
            }
        }
    }
    return false;
}

$curl = curl_init($COMPOSER_JSON_URL);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, false);
$upstream_composer_config_file = curl_exec($curl);
curl_close($curl);

$upstream_composer_config = json_decode($upstream_composer_config_file, true);
$local_repo = json_decode(file_get_contents('./composer.json'), true);


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
                // Simple package definiton. Verify version.
                $local_version = $local_definition['package']['version'];
                if ($local_version !== $upstream_version) {
                    // Upstream has a new version, add it to the list.
                    $local_repo['repositories'][$key]['package'] = [
                        $local_definition['package'],
                        $repository['package'],
                    ];
                }
            }
            else {
                // Mutiple defined versions, verify version doesn't exist.
                $package_versions = array_column($local_definition['package'], 'version');
                if (!in_array($upstream_version, $package_versions)) {
                    // Add repo if version not defined.
                    $local_repo['repositories'][$key]['package'][] = $repository['package'];
                }
            }
        }
        else {
            $local_repo['repositories'][] = $repository;
        }
    }
}

// Set installer paths.
$upstream_types = $upstream_composer_config['extra']['installer-types'];
if (isset($local_repo['extra']['installer-types'])) {
    // Other paths exist. Merge them.
    $local_types = $local_repo['extra']['installer-types'];
    $local_repo['extra']['installer-types'] = array_unique(array_merge($local_types, $upstream_types));
}
else {
    // No other paths, use upstream.
    $local_repo['extra']['installer-types'] = $upstream_types;
}

if (isset($local_repo['extra']['installer-paths'])) {
    $local_installers = &$local_repo['extra']['installer-paths'];
    // Other paths are configured. Merge configuration.
    foreach ($upstream_composer_config['extra']['installer-paths'] as $installer_path => $packages) {
        if (isset($local_installers[$installer_path])) {
            $local_installers[$installer_path] = array_unique(array_merge($packages, $local_installers[$installer_path]));
        }
        else {
            $local_installers[$installer_path] = $packages;
        }
    }
}
else {
    // No other configured paths, just add ours.
    $local_repo['extra']['installer-paths'] = $upstream_composer_config['extra']['installer-paths'];
}

$json_options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
$composer_json = json_encode($local_repo, $json_options);
file_put_contents('./composer.json',  $composer_json . PHP_EOL);
