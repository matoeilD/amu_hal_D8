<?php

namespace Drupal\amu_hal\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use Drupal\user\Entity\User;
use GuzzleHttp\Exception\ConnectException;


/**
 * Provides a 'HalPublicationBlock' Block.
 *
 * permet plusiers methodes de filtres pour la récupération de données HAL
 * également au choix le mode de display
 *
 * @Block(
 *   id = "HalPublicationBlock",
 *   admin_label = @Translation("Hal publications"),
 * )
 */
class HalPublicationBlock extends BlockBase implements BlockPluginInterface
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {

    //retrieve module global parameters
    $module_global_config = \Drupal::configFactory()->getEditable('amu_hal.settings');
    $amu_hal_url_ws = $module_global_config->get('amu_hal_url_ws');
    $docType_s= $module_global_config->get('docType_s');
    $hal_date_start= $module_global_config->get('hal_date_start');
    //retrieve bloc parameters
    $config = $this->getConfiguration();
    $isDisplay='block-display-true';

    //modif by call-to-action
    //TODO filter params makes all block of the same page changed when reloaded
    $year_param = \Drupal::request()->query->get('year') ?: null;
    if ($year_param){
      $config['year_filter'] = $year_param;
      $config['call-to-actions-widget_select'] = 'year_navigation';
    }

    $row_max_unset = \Drupal::request()->query->get('row_max_unset') ?: null;
    if ($row_max_unset){
      $config['last_publications_max_number_display'] = 2000;
      $config['year_filter'] = (int)date('Y');
      $config['call-to-actions-widget_select'] = 'year_navigation';
    }

    //todo enlever les champs non necessaires de requetes et homogeneiser
    //todo doctypes sauvé dans le config install n'est pas utilisé
    // url build-up
    $url='';
    switch ($config['retrieval_method_choice']) {

      case 'by_halId_s':
        $url = $amu_hal_url_ws . 'search/' . $config['halId_s'] . '?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=docType_s:(ART+OR+COMM+OR+POSTER+OR+OUV+OR+DOUV+OR+PATENT+OR+OTHER)';
        break;

      case 'by_structId_i':
        $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=structId_i:' . $config['structId_i'] . '+docType_s:(ART+OR+COMM+OR+POSTER+OR+OUV+OR+DOUV+OR+PATENT+OR+OTHER)';
        break;

      case 'by_authIdHal_i':
        $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=authIdHal_i:' . $config['authIdHal_i'] . '';
        break;

      case 'by_user_fields':
        //default
        $url = $amu_hal_url_ws;

        $current_path = \Drupal::service('path.current')->getPath();
        $exploded_path = explode('/', $current_path);
        $uid = end($exploded_path);
        if (prev($exploded_path) == 'user') {
          $user = User::load($uid);
          if ($user) {
            if ($user->hasField('field_publications_block_select')) {
              if($user->get('field_publications_block_select')->getString() =='selected publications')
                $isDisplay='block-display-none';
            }

            //le dernier prevaut
            if ($user->hasField('field_cn_ldap')) {
              $authLastNameFirstName_s = '"' . $user->get('field_cn_ldap')->getString() . '"';
              $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=authLastNameFirstName_s:(' . $authLastNameFirstName_s . ')';
            }
            if ($user->hasField('field_formes_auteur')) {
              $tabFormesAuteur = $user->get('field_formes_auteur')->getString();
              $tabFormesAuteur = preg_split("/\\r\\n|\\r|\\n/", $tabFormesAuteur);
              array_walk_recursive($tabFormesAuteur, function (&$value) {
                $value = ucwords(strtolower($value));
              });
              $authLastNameFirstName_s = '"';
              $authLastNameFirstName_s .= implode('" OR "', $tabFormesAuteur);
              $authLastNameFirstName_s .= '"';
              $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=authLastNameFirstName_s:(' . $authLastNameFirstName_s . ')';
            }
            if ($user->hasField('field_authidhal_i')) {
              $authIdHal_I = $user->get('field_authidhal_i')->getString();
              if ($authIdHal_I && '' != $authIdHal_I)
                $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=authIdHal_i:' . $authIdHal_I . '';
            }


          }
        }
        break;


      case 'by_authLastNameFirstName_s':
        //les noms sont concaténés
        //si le champ collection ou structure est renseigné il est ajouté à la requete
        $tabFormesAuteur = preg_split("/\\r\\n|\\r|\\n/", $config['authLastNameFirstName_s']);
        array_walk_recursive($tabFormesAuteur, function (&$value) {
          $value = ucwords(strtolower($value));
        });
        $authLastNameFirstName_s = '"';
        $authLastNameFirstName_s .= implode('" OR "', $tabFormesAuteur);
        $authLastNameFirstName_s .= '"';

        if (($config['halId_s'] != 'HAL_ID') && (!(empty($config['halId_s']))))
          $url = $amu_hal_url_ws . 'search/' . $config['halId_s'] . '?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=authLastNameFirstName_s:(' . $authLastNameFirstName_s . ')';
        elseif (($config['structId_i'] != 'Identifiant HAL de la structure') && (!(empty($config['structId_i']))))
          $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=labStructId_i:' . $config['structId_i'] . '+authLastNameFirstName_s:(' . $authLastNameFirstName_s . ')';
        else
          $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&sort=producedDate_tdate+desc&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=authLastNameFirstName_s:(' . $authLastNameFirstName_s . ')';
        break;

      //TODO afficher dans l'ordre ou ca a été renseigné
      case 'by_docid':
        $tabdocids = preg_split("/\\r\\n|\\r|\\n/", $config['docid']);
        $end = end($tabdocids);
        $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=docid:(';
        foreach ($tabdocids as $docid) {
          if ($end != $docid)
            $url .= $docid . ' OR ';
          else
            $url .= $docid . ')';
        }
        $url .= '+docType_s:(ART+OR+COMM+OR+POSTER+OR+OUV+OR+DOUV+OR+PATENT+OR+OTHER)';
        break;

      //TODO afficher dans l'ordre ou ca a été renseigné
      //TODO display none si selected publications non renseigné
      case 'by_user_docid':
        //default
        $url = $amu_hal_url_ws;
        $current_path = \Drupal::service('path.current')->getPath();
        $exploded_path = explode('/', $current_path);
        $uid = end($exploded_path);
        if (prev($exploded_path) == 'user') {
          $user = User::load($uid);
          if ($user) {
            if ($user->hasField('field_publications_block_select')) {
              if($user->get('field_publications_block_select')->getString() =='all publications')
                $isDisplay='block-display-none';
            }

            if ($user->hasField('field_docids')) {
              $docids = $user->get('field_docids')->getString();
              $tabdocids = preg_split("/\\r\\n|\\r|\\n/", $docids);
              $end = end($tabdocids);
              $url = $amu_hal_url_ws . 'search/?rows=' . $config['last_publications_max_number_display'] . '&fl=title_s,en_title_s,label_s,en_label_s,docType_s,authIdHal_s,halId_s,structId_i,uri_s,keyword_s,en_keyword_s,authLastNameFirstName_s,journalTitle_s&fq=docid:(';
              foreach ($tabdocids as $docid) {
                if ($end != $docid)
                  $url .= $docid . ' OR ';
                else
                  $url .= $docid . ')';
              }
              $url .= '+docType_s:(ART+OR+COMM+OR+POSTER+OR+OUV+OR+DOUV+OR+PATENT+OR+OTHER)';
            }
          }
        }
        break;

      default:
        $url=$amu_hal_url_ws;
        break;
    }
    if (null !=$config['year_filter'] )
      $url .='&fq=producedDateY_i:' . $config['year_filter'];


    try {
      //var_dump($url);

      $client = new Client();
      $response = $client->request('GET', $url);
      $content = json_decode($response->getBody(), true);
      //$content=$response->getBody();
    }
    catch (ConnectException $e) {
      \Drupal::logger('amu_hal')->error('cette url n est pas bonne'.$url);
    }

    switch ($config['call-to-actions-widget_select']) {
      case 'year_navigation':
        $current_date = (int)date('Y');
        $year_tab = array();
        do {
          $year_tab[] = $current_date;
          $current_date--;
        } while ($current_date != $hal_date_start);
        break;
      case 'row_max_unset':
        $row_max_unset=true;
        break;
      case 'internal_link':
        $internal_link=$config['internal_link'];
        break;
    }

    //le nom du twig file name defined in .module amu_hal_theme
    if (  $config['display_mode_select'] == 'academic_view'){
      $theme='block-AcademicDisplay';
    }
    else {
      $theme='block-FancyDisplay';
    }

    $skin='amu_hal_skin_'.$config['skin_select'];

    $cache=array(
      'max-age'=> 0
    );



    return array(
      '#cache' => $cache,
      '#theme' => $theme,
      "#content" => $content,
      '#url' => $url,
      '#docTypes_array' => $docType_s,
      '#year_tab' => $year_tab,
      '#row_max_unset'=> $row_max_unset,
      '#internal_link'=> $internal_link,
      '#isDisplay' => $isDisplay,
      '#attached' => array(
        'library' => array(
          'amu_hal/amu_hal_library',
          'amu_hal/'.$skin
        ))
    );
  }

  /**
   * {@inheritdoc}
   *
   * custom parameters for instances of block
   */
  public function blockForm($form, FormStateInterface $form_state)
  {

    $form = parent::blockForm($form, $form_state);
    $module_global_config = \Drupal::configFactory()->getEditable('amu_hal.settings');
    $config = $this->getConfiguration();

    $form['retrieval_method'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Retrieval Method'),
  );

    $form['retrieval_method']['retrieval_method_choice'] = [
      '#type' => 'radios',
      '#title' => $this->t('Récuperer les publications selon:'),
      '#description' => $this->t('détermine les champs suivants à utiliser pour la requete '),
      '#default_value' => (!empty($config['retrieval_method_choice'])) ? $config['retrieval_method_choice'] : 'by_halId_s',
      '#options' => array(
        'by_halId_s' => $this->t('l\'identifiant HAL de la collection( halId_s )'),
        'by_structId_i' => $this->t('l\'identifiant HAL de la structure ( structureId_i ) '),
        'by_authIdHal_i' => $this->t('l\'identifiant HAL de l\'auteur ( authIdHal_i )'),
        'by_user_fields' => $this->t('l\'identifiant HAL de l\'auteur ( authIdHal_i ) renseigné depuis le champ field_authidhal_i de /user/uid ou si manquant, le(s forme(s) auteur depuis le champ field_formes_auteur'),
        'by_authLastNameFirstName_s' => $this->t('le(s) forme(s) auteur( authLastNameFirstName_s ) +(optionnel)  ( halId_s || structureId_i ) '),
        'by_docid' => $this->t('une liste définie de documents significatifs ( docid)'),
        'by_user_docid' => $this->t('une liste définie de documents significatifs ( docid) renseignée depuis le champ field_docids de /user/uid'),
      )
    ];

    $form['retrieval_method']['halId_s'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Identifiant HAL de la collection'),
      '#description' => $this->t('le HAL halId_s'),
      '#default_value' => (!empty($config['halId_s'])) ? $config['halId_s'] : $module_global_config->get('halId_s')
    ];

    $form['retrieval_method']['structId_i'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Identifiant HAL de la structure'),
      '#description' => $this->t('le HAL structId_i'),
      '#default_value' => (!empty($config['structId_i'])) ? $config['structId_i'] : $module_global_config->get('structId_i')
    ];

    $form['retrieval_method']['authIdHal_i'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Identifiant HAL de l\'auteur'),
      '#description' => $this->t('le HAL authIdHal_i '),
      '#default_value' => (!empty($config['authIdHal_i'])) ? $config['authIdHal_i'] : ''
    ];

    $form['retrieval_method']['authLastNameFirstName_s'] = [
      '#type' => 'textarea',
      '#title' => $this->t('HAL Forme-Auteur(s) de l\'auteur'),
      '#description' => $this->t('Renseigner le(s) Forme-auteur(s) de l\'auteur, un par ligne, sous la forme authLastName_s authFirstName_s, La valeur concanténée sera considérée comme le HAL authLastNameFirstName_s. Le champ collection ou structure si renseigné sera considéré'),
      '#default_value' => (!empty($config['authLastNameFirstName_s'])) ? $config['authLastNameFirstName_s'] : ''
    ];

    $form['retrieval_method']['docid'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Identifiant-HAL des documents'),
      '#description' => $this->t('Renseigner le HAL docid des documents à afficher, un par ligne'),
      '#default_value' => (!empty($config['docid'])) ? $config['docid'] : ''
    ];

    $form['sorting_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('More options'),
    );

    $form['sorting_options']['sorting_options choices'] = [
      '#type' => 'select',
      '#title' => $this->t('Sorting options'),
      '#description' => $this->t('et non pas plusieurs options...un jour peut être'),
      '#options' => [
        'last publications' => $this->t('publications les plus récentes')
      ],
      '#default_value' => (!empty($config['sorting_options choices'])) ? $config['sorting_options choices'] : 'last publications'
    ];

    $form['sorting_options']['last_publications_max_number_display'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre max à afficher'),
      '#description' => $this->t('Nombre max de publications'),
      '#default_value' => (!empty($config['last_publications_max_number_display'])) ? $config['last_publications_max_number_display'] : 5
    ];

    $form['filter_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('filter options'),
    );

    $form['filter_options']['filter_options_choices'] = [
      '#type' => 'select',
      '#title' => $this->t('filter options'),
      '#description' => $this->t('et non pas plusieurs options...un jour peut être'),
      '#options' => [
        'year_filter_option' => $this->t('filtrer par année')
      ],
      '#default_value' => (!empty($config['filter_options_choices'])) ? $config['filter_options_choices'] : 'year_filter_option'
    ];

    $form['filter_options']['year_filter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Année'),
      '#description' => $this->t('ex:2014'),
      '#default_value' => (!empty($config['year_filter'])) ? $config['year_filter'] : null
    ];

    $form['call-to-actions-widget'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('call-to-action widgets'),
    );

    $form['call-to-actions-widget']['call-to-actions-widget_select'] = [
      '#type' => 'select',
      '#title' => $this->t('widget options'),
      '#description' => $this->t('choisir un widget'),
      '#options' => [
        'none'=>$this->t('none'),
        'year_navigation' => $this->t('exposed filter :year'),
        'row_max_unset' => $this->t('more publications ( block refresh )'),
        'internal_link' => $this->t('more publications ( internal link )'),
      ],
      '#default_value' => (!empty($config['call-to-actions-widget_select'])) ? $config['call-to-actions-widget_select'] : 'none'
    ];

    $form['call-to-actions-widget']['internal_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('internal link'),
      '#description' => $this->t('de type /node/56'),
      '#default_value' => (!empty($config['internal_link'])) ? $config['internal_link'] : ''
    ];

    $form['display_mode'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Display mode'),
    );
    $form['display_mode']['display_mode_select'] = [
      '#type' => 'select',
      '#title' => $this->t('display mode'),
      '#description' => $this->t('Choisir parmi les options possibles'),
      '#options' => [
        'academic_view' => $this->t('academic view'),
        'fancy_view' => $this->t('fancy view'),
      ],
      '#default_value' => (!empty($config['display_mode_select'])) ? $config['display_mode_select'] : 'academic view'
    ];

    $form['skin'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Skin'),
    );
    $form['skin']['skin_select'] = [
      '#type' => 'select',
      '#title' => $this->t('skin'),
      '#description' => $this->t('Choisir parmi les options possibles'),
      '#options' => [
        'generic' => $this->t('neutral'),
        'pink_power' => $this->t('pink power'),
      ],
      '#default_value' => (!empty($config['skin_select'])) ? $config['skin_select'] : 'neutral'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $form_state_values = $form_state->getValues();

    $this->configuration['retrieval_method_choice'] = $form_state_values['retrieval_method']['retrieval_method_choice'];
    $this->configuration['halId_s'] = $form_state_values['retrieval_method']['halId_s'];
    $this->configuration['structId_i'] = $form_state_values['retrieval_method']['structId_i'];
    $this->configuration['authIdHal_i'] = $form_state_values['retrieval_method']['authIdHal_i'];
    $this->configuration['authLastNameFirstName_s'] = $form_state_values['retrieval_method']['authLastNameFirstName_s'];
    $this->configuration['docid'] = $form_state_values['retrieval_method']['docid'];
    $this->configuration['sorting_options choices'] = $form_state_values['sorting_options']['sorting_options choices'];
    $this->configuration['year_filter'] = $form_state_values['filter_options']['year_filter'];
    $this->configuration['last_publications_max_number_display'] = $form_state_values['sorting_options']['last_publications_max_number_display'];
    $this->configuration['call-to-actions-widget_select'] = $form_state_values['call-to-actions-widget']['call-to-actions-widget_select'];
    $this->configuration['internal_link'] = $form_state_values['call-to-actions-widget']['internal_link'];
    $this->configuration['display_mode_select'] = $form_state_values['display_mode']['display_mode_select'];
    $this->configuration['skin_select'] = $form_state_values['skin']['skin_select'];
  }
}
