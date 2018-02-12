<?php
/**
 *  This file was generated with crodas/SimpleView (https://github.com/crodas/SimpleView)
 *  Do not edit this file.
 *
 */

namespace {


    class base_template_281f309710c8d03adaf2a02f1b8ed5b8d690a9d2
    {
        protected $parent;
        protected $child;
        protected $context;

        public function yield_parent($name, $args)
        {
            $method = "section_" . sha1($name);

            if (is_callable(array($this->parent, $method))) {
                $this->parent->$method(array_merge($this->context, $args));
                return true;
            }

            if ($this->parent) {
                return $this->parent->yield_parent($name, $args);
            }

            return false;
        }

        public function do_yield($name, Array $args = array())
        {
            if ($this->child) {
                // We have a children template, we are their base
                // so let's see if they have implemented by any change
                // this section
                if ($this->child->do_yield($name, $args)) {
                    // yes!
                    return true;
                }
            }

            // Do I have this section defined?
            $method = "section_" . sha1($name);
            if (is_callable(array($this, $method))) {
                // Yes!
                $this->$method(array_merge($this->context, $args));
                return true;
            }

            // No :-(
            return false;
        }

    }

    /** 
     *  Template class generated from store.tpl
     */
    class class_1470f1bf1699480bf5a540d4b9196dbe32779c5c extends base_template_281f309710c8d03adaf2a02f1b8ed5b8d690a9d2
    {

        public function hasSection($name)
        {

            return false;
        }


        public function renderSection($name, Array $args = array(), $fail_on_missing = true)
        {
            if (!$this->hasSection($name)) {
                if ($fail_on_missing) {
                    throw new \RuntimeException("Cannot find section {$name}");
                }
                return "";
            }

        }

        public function enhanceException(Exception $e, $section = NULL)
        {
            if (!empty($e->enhanced)) {
                return;
            }

            $message = $e->getMessage() . "( IN " . 'store.tpl';
            if ($section) {
                $message .= " | section: {$section}";
            }
            $message .= ")";

            $object   = new ReflectionObject($e);
            $property = $object->getProperty('message');
            $property->setAccessible(true);
            $property->setValue($e, $message);

            $e->enhanced = true;
        }

        public function render(Array $vars = array(), $return = false)
        {
            try {
                return $this->_render($vars, $return);
            } catch (Exception $e) {
                if ($return) ob_get_clean();
                $this->enhanceException($e);
                throw $e;
            }
        }

        public function _render(Array $vars = array(), $return = false)
        {
            $this->context = $vars;

            extract($vars);
            if ($return) {
                ob_start();
            }

            echo "<?php\n/**\n *  This file was generated with crodas/Remember (https://github.com/crodas/Remember)\n *  Do not edit this file.\n *\n */\n\n\$data = NULL;\n\n";
            foreach((array)$files as $f) {

                $this->context['f'] = $f;
                echo "\$file = ";
                var_export($f);
                echo ";\nif (!is_readable(\$file) || filemtime(\$file) > ";
                echo filemtime($f) . ") {\n    \$valid = false;\n    return;\n}\n";
            }
            echo "\n";
            if ($serialized) {
                echo "\$data = unserialize(";
                var_export($sData);
                echo ");\n";
            }
            else {
                echo "\$data = ";
                var_export($sData);
                echo ";\n";
            }
            echo "\n\$valid = true;\n";

            if ($return) {
                return ob_get_clean();
            }

        }
    }

}

namespace Remember {


    class Templates
    {
        public static function getAll()
        {
            return array (
                0 => 'store',
            );
        }

        public static function getAllSections($name, $fail = true)
        {
            switch ($name) {
            default:
                if ($fail) {
                    throw new \RuntimeException("Cannot find section {$name}");
                }

                return array();
            }
        }

        public static function exec($name, Array $context = array(), Array $global = array())
        {
            $tpl = self::get($name);
            return $tpl->render(array_merge($global, $context));
        }

        public static function get($name, Array $context = array())
        {
            static $classes = array (
                'store.tpl' => 'class_1470f1bf1699480bf5a540d4b9196dbe32779c5c',
                'store' => 'class_1470f1bf1699480bf5a540d4b9196dbe32779c5c',
            );
            $name = strtolower($name);
            if (empty($classes[$name])) {
                throw new \RuntimeException("Cannot find template $name");
            }

            $class = "\\" . $classes[$name];
            return new $class;
        }
    }

}
