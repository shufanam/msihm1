<?php
/**
 * Base abstract class for HandyMan action pages
 */
abstract class hmController {
    protected $templateFile = 'empty';
    protected $viewType = 'page';
    protected $cache = true;

    function __construct(HandyMan &$hm,array $config = array()) {
        $this->hm =& $hm;
        $this->modx =& $hm->modx;
        $this->config = array_merge(array(),$config);
    }

    abstract public function setup();
    abstract public function process();

    public function getPageId() {}
    public function getPageTitle() {
        return 'HandyMan';
    }

    public function initialize() {
        $this->setup();
    }
    
    public function render(array $options = array()) {
        $this->config = array_merge($this->config,$options);
        $placeholders = $this->process();
        if (empty($placeholders)) $placeholders = array();

        $output = $this->hm->getTpl($this->templateFile,$placeholders);
        $output = $this->_wrap($output);

        return $output;
    }

    private function _wrap($body = '') {
        $output = $this->renderPageType($body);
        return $this->getHeader().$output.$this->getFooter();
    }

    protected function getHeader() {
        return $this->hm->getTpl('header',array(
            'title' => $this->getPageTitle(),
        ));
    }

    protected function getFooter() {
        return $this->hm->getTpl('footer');
    }

    protected function getLicense() {
        return $this->hm->getTpl('license',array(
            'license' => $this->hm->getLicenseName(),
        ));
    }

    protected function renderPageType($body = '') {
        $id = $this->getPageId();
        $id = !empty($id) ? $id : $this->config['hma'];
        $cache = $this->cache === false ? ' data-cache="false" ' : '';

        $output = '';
        $placeholders = array(
            'id' => $id,
            'cache' => $cache,
            'title' => $this->getPageTitle(),
            'content' => $body,
            'license' => $this->getLicense(),
            'baseUrl' => $this->hm->webroot,
        );
        // Depending on the type of page (determined by the $meta['view'] option) we'll output something here.
        switch ($this->viewType) {
            // First "view" is a dialog window, which doesn't need as many buttons and stuff. We do add a "Close window" button here.
            case 'dialog':
                $output .= $this->hm->getTpl('views/dialog',$placeholders);
                break;

            // The default view is the "page" one, which has a back & home button and just the main content after that.
            case 'page':
            default:
                $output .= $this->hm->getTpl('views/page',$placeholders);
            break;
        }
        return $output;
    }

    public function processActions(array $actions = array()) {
        $output = array();
        foreach ($actions as $action) {
            if (isset($action['dialog'])) {
                $action['dialog'] = ' data-rel="dialog"';
                $action['transition'] = $action['transition'] ? $action['transition'] : 'pop';
            } else {
                $action['transition'] = $action['transition'] ? $action['transition'] : 'slide';
                $action['dialog'] = '';
            }
            $action['icon'] = $action['icon'] ? $action['icon'] : 'arrow-r';
            $action['ajaxreset'] = ($action['reset']) ? ' data-ajax="false"' : '';
            $lps = '';
            if (count($action['linkparams']) > 0) {
                foreach ($action['linkparams'] as $lp => $lpv) {
                    $lps .= '&'.$lp.'='.$lpv;
                }
            }
            $action['link'] = $this->hm->webroot.'index.php?hma='.$action['action'].$lps;
            
            $output[] = $this->hm->getTpl('widgets/rowAction',$action);
        }
        return implode("\n",$output);
    }

    public function redirect($action,array $params = array()) {
        $params['action'] = $action;
        $url = $this->hm->webroot.'index.php?'.http_build_query($params);
        $this->modx->sendRedirect($url);
    }

/*
    public function processActions($actionMap) {
        $ret = '';
        foreach ($actionMap as $a) {
            if (isset($a['dialog'])) {
                $dialog = ' data-rel="dialog"';
                $transition = ($a['transition']) ? $a['transition'] : 'pop';
            } else {
                $transition = ($a['transition']) ? $a['transition'] : 'slide';
                $dialog = '';
            }
            $icon = ($a['icon']) ? $a['icon'] : 'arrow-r';
            $ajaxreset = ($a['reset']) ? ' data-ajax="false"' : '';
            if (count($a['linkparams']) > 0) {
                $lps = '';
                foreach ($a['linkparams'] as $lp => $lpv) {
                    $lps .= '&'.$lp.'='.$lpv;
                }
            }
            $link = $this->webroot.'index.php?hma='.$a['action'].$lps;
            if ((isset($a['count'])) && ($a['count'] > 0)) { $count = '<p class="ui-li-count">'.$a['count'].'</p>'; }
            if (isset($a['aside'])) { $aside = '<p>'.$a['aside'].'</p>'; }
            $ret .= '<li data-icon="'.$icon.'">
                <a href="'.$link.'" data-transition="'.$transition.'"'.$ajaxreset.$dialog.'>
                    <h3>'.$a['linktext'].'</h3>'.
                    $aside.
                    $count.
                    '</a>
                </li>';
            unset ($lps,$lp,$link,$count,$aside);
        }
        return $ret;
    }*/
}