<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Data\Data;
use RocketTheme\Toolbox\Event\Event;
use ReflectionProperty;

/**
 * Class SequentialFormPlugin
 * @package Grav\Plugin
 */
class SequentialFormPlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }
        // Enable the main event we are interested in
        $this->enable([
            'onTwigTemplatePaths' => ['onTwigTemplatePaths',0],
            'onTwigSiteVariables' => ['onTwigSiteVariables',0],
            'onFormProcessed' => ['onFormProcessed', 0],
            'onFormValidationError' => ['onFormValidationError', 0]
        ]);
    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
        if ($this->config->get('plugins.sequential-form.built_in_css')) {
            $this->grav['assets']->addCss('plugin://sequential-form/css/sqforms.css');
        }
    }
    public function onTwigSiteVariables() {
        // Is there a form with a sequence on this page?
        if ( ! empty($this->grav['page']->header()->{'form'}['process'][0]['sequence']) )
            $sequence = $this->grav['page']->header()->{'form'}['process'][0]['sequence'];
        else $sequence = null;
        if ( $sequence )  {
            $this->grav['twig']->twig_vars['sequence'] = $sequence;
        }
    }

    public function onFormProcessed(Event $event)
    {
        $action = $event['action'];
        $params = $event['params'];
        $form = $event['form'];
        switch ($action) {
            case 'next_page':
                $seq_name = 'sequence_';
                $header = $this->grav['page']->header()->{'sequence'};
                if ( isset($header['name']) ) $seq_name .= $header['name'];
                    else $seq_name .=   'default';
                $sumForm = $this->grav['session']->getFlashObject( $seq_name ); // this also removes the object from the session
                // trap a sequence reset button
                if (isset($_POST['task']) && $_POST['task'] == 'sequence_reset' ) {
                    $this->grav['session']->getFlashObject('form' ); // reset form
                    $sequence = $sumForm->getData()->toArray()['_sequence'];
                    $this->grav->redirect($sequence['origin'] . '/' . $url);
                }
                if ( $sumForm ) {
                    // sequence has started so we have sequence data
                    $data = $sumForm->getData()->toArray();
                    $sequence = $data['_sequence'];
                    $stage = $sequence['stage'];
                    // add new data fileds, if any, to cumulative form
                    $newvalues = $form->getData()->toArray();
                    if ( $newvalues )
                        foreach ( $newvalues as $key => $value ) {
                            $sumForm->setData( $key, $value );
                            $sumForm->setValue($key, $value );
                        };
                    if ( $stage >= sizeof($sequence['routes']) ) {
                         // end condition for sequence. So return with Form set to
                         // accumulated Form without sequence dataset
                         $sumForm->setData('_sequence', null);
                         $rp = new ReflectionProperty('Grav\Plugin\Form', 'items');
                         $rp->setAccessible(true);
                         $items = $rp->getValue($sumForm);
                         $procs = $items['process'];
                         do {
                             $action = array_shift($procs);
                         } while ( \key($action) !== 'sequence' );
                         $items['process'] = $procs;
                         $rp->setValue($sumForm,$items);
                         $pages = $this->grav['pages'];
                         $page = $pages->dispatch($sequence['origin'], true);
                         unset($this->grav['page']);
                         $this->grav['page'] = $page;
                         $sumForm->post();
                     } else {
                         // redirect to the next page with its form providing new twig var
                         $url = ((string)$sequence['routes'][$stage]);
                         $sequence['stage'] = $stage + 1; // for next stage
                         $sequence['form'] = $url;
                         $sumForm->setData('_sequence', $sequence);
                         $this->grav['session']->setFlashObject($seq_name, $sumForm );
                         $this->grav->redirect($sequence['origin'] . '/' . $url);
                    }
                }
                break;
            case 'sequence':
                // First time through, so create a FlashObject with the initial Form parameters
                if ( sizeof($params['routes']) >= 1 ) { // if no sequence data then exit without change
                    $seq_name = 'sequence_';
                    if (isset($params['name']) ) $seq_name .=  $params['name'];
                    else $seq_name .= 'default';
                    $url = ((string)$params['routes'][0]);
                    $origin = $this->grav['page']->route() ;
                    if ($origin === '/') { // sequence form is on home page, so get page's slug
                        $origin = $this->grav['page']->header()->{'slug'};
                    }
                    $sequence = [
                        'stage' => 1,
                        'routes' => $params['routes'] ,
                        'form' => $url,
                        'origin' => $origin,
                        'icons' => $params['icons']
                    ];
                    // stage= 1 for the first form that defines the sequence, and first redirect here
                    $form->setData('_sequence', $sequence);
                    $this->grav['session']->setFlashObject( $seq_name, $form );
                    $this->grav->redirect($origin . '/' . $url);
                }
                break;
        }
    }

   public function onFormValidationError(Event $event)
    {
        $form = $event['form'];

        if (isset($event['message'])) {
            $form->status = 'error';
            $form->message = $event['message'];
            $form->messages = $event['messages'];
        }
        $twig = $this->grav['twig'];
        $twig->twig_vars['form'] = $form;

        // cause return to initial sequence form.
                $uri = $this->grav['uri'];
                $route = $uri->route();
                preg_match('/^(.+)\/[^\/]+$/',$route,$match);
                $pages = $this->grav['pages'];
                $page = $pages->dispatch($match[1], true);
                if ($page) {
                    unset($this->grav['page']);
                    $this->grav['page'] = $page;
                }

        $event->stopPropagation();
    }
}
