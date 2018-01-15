<?php


namespace Drupal\amu_hal\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

/**
 * @author M.dandonneau
 *
* for created routes publication/{}
*/
class AmuHalController extends ControllerBase {

/**
* {@inheritdoc}
*/
  public function publicationDetail($halId_s) {

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $module_global_config = \Drupal::configFactory()->getEditable('amu_hal.settings');
    $amu_hal_url_ws = $module_global_config->get('amu_hal_url_ws');
    $docType_s= $module_global_config->get('docType_s');

    $url=$amu_hal_url_ws . 'search/?fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,abstract_s,en_abstract_s,fileMain_s,journalTitle_s&fq=halId_s:' .$halId_s. '+docType_s:(ART+OR+COMM+OR+POSTER+OR+OUV+OR+DOUV+OR+PATENT+OR+OTHER)';

    try {
      $client = new Client();
      $response = $client->request('GET', $url);
      $content = json_decode($response->getBody(), true);
    }
    catch (ConnectException $e) {
      \Drupal::logger('amu_hal')->error('cette url n est pas bonne'.$url);
    }

    $theme='page-PublicationDetails';

    $build = array(
      '#theme' => $theme,
      "#content" => $content,
      '#url' => $url,
      '#language' => $language,
      '#docTypes_array' => $docType_s,
      /*'#type' => 'markup',
      '#markup' => t('Hello World detail!'.$docid),*/
    );
    return $build;
  }

}
