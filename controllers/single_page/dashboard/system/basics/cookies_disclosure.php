<?php
namespace Concrete\Package\FreeCookiesDisclosure\Controller\SinglePage\Dashboard\System\Basics;

use Core;
use Page;
use Package;
use Request;
use \Mainio\C5\Twig\Page\Controller\DashboardPageController;

defined('C5_EXECUTE') or die("Access Denied.");

class CookiesDisclosure extends DashboardPageController
{

    use \Mainio\C5\SymfonyForms\Controller\Extension\SymfonyFormsExtension;
    use \Mainio\C5\ControllerExtensions\Controller\Extension\DoctrineEntitiesExtension;

    protected $colorProfiles;

    public function __construct()
    {
        $p = Page::getCurrentPage();
        parent::__construct($p);
        $this->colorProfiles = array('' => t('Dark'), 'light' => t('Light'));
    }

    public function view()
    {
        $config = $this->getConfig();

        $colorProfile = $config->get('cookies.disclosure_color_profile');

        if (!array_key_exists($colorProfile, $this->colorProfiles)) {
            $colorProfileCustom = $colorProfile;
            $colorProfile = 'custom';
        }

        $this->colorProfiles['custom'] = t('Custom');

        $hideInterval = $config->get('cookies.disclosure_hide_interval');
        $hideInterval = $hideInterval > 0 ? $hideInterval : null;

        $debug = (int)$config->get('cookies.disclosure_debug');
        $debug = ($debug === 1);

        $this->set('has_multilingual', Core::make('multilingual/detector')->isEnabled());

        $formArray = array(
            'alignment' => $config->get('cookies.disclosure_alignment'),
            'color_profile' => $colorProfile,
            'color_profile_custom' => $colorProfileCustom,
            'hide_interval' => $hideInterval,
            'debug' => $debug,
        );

        $form = $this->buildForm($formArray);
        $this->set('formObject', $form);
        $this->set('form', $form->createView());
    }

    public function save()
    {
        $this->view();

        $form = $this->get('formObject');
        if ($this->saveForm($form)) {
            $this->flash('success', t("Display settings successfully saved."));
            $this->redirect($this->c->getCollectionPath());
        }

    }

    protected function buildForm($formArray, $options = array())
    {
        $action = $this->action('save');
        $formFactory = $this->getFormFactory();
        $builder = $formFactory->createBuilder('form', $formArray, array_merge(array(
            'action' => $action,
        ), $options))
            ->add('alignment', 'choice', array(
                'label' => t('Alignment'),
                'empty_value' => false,
                'choices' => array(
                    'top' => t("Top of the Page"),
                    'bottom' => t("Bottom of the Page"),
                ),
            ))
            ->add('color_profile', 'choice', array(
                'label' => t('Color Profile'),
                'empty_value' => true,
                'required' => false,
                'choices' => $this->colorProfiles,
            ))
            ->add('color_profile_custom', 'text', array(
                'label' => t('CSS Suffix for Color Profile'),
                'required' => false,
            ))
            ->add('hide_interval', 'number', array(
                'label' => t('Hide Interval'),
                'required' => false,
            ))
            ->add('debug', 'checkbox', array(
                'label' => t('Enable Debug Mode'),
                'required' => false,
            ));

        return $builder->getForm();
    }

    protected function saveForm($form)
    {
        $request = Request::getInstance();

        // Only handle the saving process if it's a post request.
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $this->saveSettings($data);

                return true;
            } else {
                // The boolean 'true' is for getting all the errors on the form.
                $errors = $form->getErrors(true);
                foreach ($errors as $err) {
                    $this->error->add($err->getMessage());
                }
            }
        }

        return false;
    }

    protected function saveSettings($data)
    {
        $config = $this->getConfig();

        $alignment = $data['alignment'];
        $colorProfile = trim($data['color_profile']);

        if ($colorProfile == 'custom') {
            $colorProfile = trim($data['color_profile_custom']);
        }

        $config->save('cookies.disclosure_alignment', $alignment);
        $config->save('cookies.disclosure_color_profile', $colorProfile);

        $hideInterval = intval($data['hide_interval']);
        if ($hideInterval > 0) {
            $config->save('cookies.disclosure_hide_interval', $hideInterval);
        } else {
            $config->save('cookies.disclosure_hide_interval', null);
        }

        if ($data['debug']) {
            $config->save('cookies.disclosure_debug', 1);
        } else {
            $config->save('cookies.disclosure_debug', null);
        }
    }

    protected function getConfig()
    {
        $pkg = Package::getByID($this->c->getPackageID());
        return $pkg->getConfig();
    }

}