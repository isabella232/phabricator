<?php

final class HarbormasterBuildkiteBuildStepImplementation
  extends HarbormasterBuildStepImplementation {

  public function getName() {
    return pht('Build with Buildkite');
  }

  public function getGenericDescription() {
    return pht('Trigger a build in Buildkite.');
  }

  public function getBuildStepGroupKey() {
    return HarbormasterExternalBuildStepGroup::GROUPKEY;
  }

  public function getDescription() {
    return pht('Run a build in Buildkite.');
  }

  public function getEditInstructions() {
    $hook_uri = '/harbormaster/hook/circleci/';
    $hook_uri = PhabricatorEnv::getProductionURI($hook_uri);

    return pht(
      'Creates a Buildkite build. See [Buildkite’s Phabricator'.
      'guide](https://buildkite.com/docs/guides/phabricator) for more information.');
  }

  public function execute(
    HarbormasterBuild $build,
    HarbormasterBuildTarget $build_target) {

    $viewer = PhabricatorUser::getOmnipotentUser();
    $settings = $this->getSettings();
    $uri = $settings['uri'];
  
    $buildkite_hook_data = $this->buildkiteWebhookData(
      $build,
      $build_target,
      $viewer);

    $json_data = phutil_json_encode($buildkite_hook_data);

    $future = id(new HTTPSFuture($uri, $json_data))
      ->setMethod('POST')
      ->addHeader('Content-Type', 'application/json')
      ->addHeader('Accept', 'application/json')
      ->setTimeout(60);

    $this->resolveFutures(
      $build,
      $build_target,
      array($future));

    $this->logHTTPResponse($build, $build_target, $future, $uri);

    list($status) = $future->resolve();
    if ($status->isError()) {
      throw new HarbormasterBuildFailureException();
    }
  }

  public function getFieldSpecifications() {
    return array(
      'uri' => array(
        'name' => pht('Pipeline Trigger URI'),
        'type' => 'text',
        'required' => true,
      ),
    );
  }

  public function supportsWaitForMessage() {
    return true;
  }

  private function buildkiteWebhookData($build, $build_target, $viewer) {
    $variables = $build_target->getVariables();
    $hook_uri = PhabricatorEnv::getProductionURI(
      '/harbormaster/hook/buildkite/');

    $diffDict = array();
    $commitDict = array();

    $buildable = $build->getBuildable();
    $object = $buildable->getBuildableObject();

    if (!empty($variables['buildable.diff'])) {
      $diffDict['revisionID'] = $object->getRevisionID();
      $diffDict['dateCreated'] = $object->getDateCreated();
      $diffDict['dateModified'] = $object->getDateModified();
      $diffDict['sourceControlBaseRevision'] = $object->getSourceControlBaseRevision();
      $diffDict = $diffDict + $object->getDiffAuthorshipDict();
    }

    if (!empty($variables['buildable.commit'])) {
      // TODO: Add commit info
      // $commitDict = $commitDict + $object->getCommitData()->toDictionary();
    }

    return array(
      "variables" => $variables,
      "hook_uri" => $hook_uri,
      "diff" => $diffDict,
      "commit" => $commitDict,
    );
  }

}
