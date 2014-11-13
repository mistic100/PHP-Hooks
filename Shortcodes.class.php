<?php
/**
 * PHP Shortcodes Class
 * From PHP Hooks by Lars Moelleken
 *
 * The PHP Shortcodes Class is a fork of the WordPress shortcodes system rolled in to a class to be ported
 * into any php based system. It interfaces with PHP Hooks for shortcodes filtering.
 *
 * This class is heavily based on the WordPress plugin API and most (if not all) of the code comes from there.
 *
 *
 * @version 0.2
 * @copyright 2011 - 2014
 * @author Lars Moelleken <lars@moelleken.org>
 * @link https://github.com/voku/PHP-Hooks
 * @author Damien "Mistic" Sorel <contact@git.strangeplanet.fr>
 * @link http://www.strangeplanet.fr
 *
 * @license GNU General Public LIcense v3.0 - license.txt
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Shortcodes
 */
class Shortcodes
{
  /**
   * Hooks object attached to this instance
   *
   * @since 0.2
   * @name $hooks
   * @var Hooks
   */
  public $hooks = null;

  /**
   * Container for storing shortcode tags and their hook to call for the shortcode
   *
   * @since 0.1
   * @name $shortcode_tags
   * @var array
   */
  public $shortcode_tags = array();


  /**
   * __construct class constructor
   *
   * @access public
   * @since 0.1
   *
   * @param Hooks $hooks optional
   */
  public function __construct($hooks = null)
  {
    $this->hooks = $hooks;
    $this->shortcode_tags = array();
  }


  /**
   * Add hook for shortcode tag.
   *
   * There can only be one hook for each shortcode. Which means that if another
   * plugin has a similar shortcode, it will override yours or yours will override
   * theirs depending on which order the plugins are included and/or ran.
   *
   * The $func parameters are:
   *    - array|string $attrs hashmap of attributes or empty string
   *    - string|null $content content of the shortcode if any
   *    - string $tag shortcode name
   *    - Callable $atts_parser reference to Shortcodes::shortcode_atts method
   *
   * @since 0.1
   * @access public
   *
   * @param string   $tag  Shortcode tag to be searched in post content.
   * @param callable $func Hook to run when shortcode is found.
   */
  public function add_shortcode($tag, $func)
  {
    if (is_callable($func))
    {
      $this->shortcode_tags[$tag] = $func;
    }
  }

  /**
   * Removes hook for shortcode.
   *
   * @since 0.1
   * @access public
   *
   * @param string $tag shortcode tag to remove hook for.
   */
  public function remove_shortcode($tag)
  {
    unset($this->shortcode_tags[$tag]);
  }

  /**
   * Clear all shortcodes.
   *
   * This function is simple, it clears all of the shortcode tags by replacing the
   * shortcodes by a empty array. This is actually a very efficient method
   * for removing all shortcodes.
   *
   * @since 0.1
   * @access public
   */
  public function remove_all_shortcodes()
  {
    $this->shortcode_tags = array();
  }

  /**
   * Whether a registered shortcode exists named $tag
   *
   * @since 0.1
   * @access public
   *
   * @param string $tag
   * @return boolean
   */
  public function shortcode_exists($tag)
  {
    return array_key_exists($tag, $this->shortcode_tags);
  }

  /**
   * Whether the passed content contains the specified shortcode.
   *
   * If the second parameter is ommited, will return true whether the content
   * has any known shortcode.
   *
   * @since 0.1
   * @access public
   *
   * @param $content
   * @param $tag
   * @return bool
   */
  public function has_shortcode($content, $tag = null)
  {
    if (false === strpos( $content, '[' ))
    {
      return false;
    }

    if ($tag !== null && !$this->shortcode_exists($tag))
    {
      return false;
    }

    preg_match_all('/' . $this->get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER);
    if (empty($matches))
    {
      return false;
    }

    if ($tag === null)
    {
      return true;
    }

    foreach ($matches as $shortcode)
    {
      if ($tag === $shortcode[2])
      {
        return true;
      }
      elseif (!empty($shortcode[5]) && $this->has_shortcode($shortcode[5], $tag))
      {
        return true;
      }
    }
    return false;
  }

  /**
   * Search content for shortcodes and filter shortcodes through their hooks.
   *
   * If there are no shortcode tags defined, then the content will be returned
   * without any filtering. This might cause issues when plugins are disabled but
   * the shortcode will still show up in the post or content.
   *
   * @since 0.1
   * @access public
   *
   * @param string $content Content to search for shortcodes
   * @return string Content with shortcodes filtered out.
   */
  public function do_shortcode($content)
  {
    if (empty($this->shortcode_tags) || !is_array($this->shortcode_tags))
    {
      return $content;
    }

    $pattern = $this->get_shortcode_regex();
    $loop = 0;

    do {
      $content = preg_replace_callback(
        "/$pattern/s",
        array($this, 'do_shortcode_tag'),
        $content
      );

      $loop++;
    }
    while ($loop<10 && $this->has_shortcode($content));

    return $content;
  }

  /**
   * Retrieve the shortcode regular expression for searching.
   *
   * The regular expression combines the shortcode tags in the regular expression
   * in a regex class.
   *
   * The regular expression contains 6 different sub matches to help with parsing.
   *
   * 1 - An extra [ to allow for escaping shortcodes with double [[]]
   * 2 - The shortcode name
   * 3 - The shortcode argument list
   * 4 - The self closing /
   * 5 - The content of a shortcode when it wraps some content.
   * 6 - An extra ] to allow for escaping shortcodes with double [[]]
   *
   * @since 0.1
   * @access private
   *
   * @return string The shortcode search regular expression
   */
  private function get_shortcode_regex()
  {
    $tagnames = array_keys($this->shortcode_tags);
    $tagregexp = join('|', array_map('preg_quote', $tagnames));

    // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
    // Also, see shortcode_unautop() and shortcode.js.
    return
        '\\[' // Opening bracket
        . '(\\[?)' // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
        . "($tagregexp)" // 2: Shortcode name
        . '(?![\\w-])' // Not followed by word character or hyphen
        . '(' // 3: Unroll the loop: Inside the opening shortcode tag
        . '[^\\]\\/]*' // Not a closing bracket or forward slash
        . '(?:'
        . '\\/(?!\\])' // A forward slash not followed by a closing bracket
        . '[^\\]\\/]*' // Not a closing bracket or forward slash
        . ')*?'
        . ')'
        . '(?:'
        . '(\\/)' // 4: Self closing tag ...
        . '\\]' // ... and closing bracket
        . '|'
        . '\\]' // Closing bracket
        . '(?:'
        . '(' // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
        . '[^\\[]*+' // Not an opening bracket
        . '(?:'
        . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
        . '[^\\[]*+' // Not an opening bracket
        . ')*+'
        . ')'
        . '\\[\\/\\2\\]' // Closing shortcode tag
        . ')?'
        . ')'
        . '(\\]?)'; // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
  }

  /**
   * Regular Expression callable for do_shortcode() for calling shortcode hook.
   * @see    get_shortcode_regex for details of the match array contents.
   *
   * @since  0.1
   * @access private
   *
   * @param array $m Regular expression match array
   * @return mixed False on failure.
   */
  private function do_shortcode_tag($m)
  {
    // allow [[foo]] syntax for escaping a tag
    if ($m[1] == '[' && $m[6] == ']')
    {
      return substr($m[0], 1, -1);
    }

    $tag = $m[2];
    $attr = $this->shortcode_parse_atts($m[3]);
    $func = array($this, 'shortcode_atts');

    return $m[1] . call_user_func($this->shortcode_tags[$tag], $attr, $m[5], $tag, $func) . $m[6];
  }

  /**
   * Retrieve all attributes from the shortcodes tag.
   *
   * The attributes list has the attribute name as the key and the value of the
   * attribute as the value in the key/value pair. This allows for easier
   * retrieval of the attributes, since all attributes have to be known.
   *
   * @since 0.1
   * @access private
   *
   * @param string $text
   * @return array List of attributes and their value.
   */
  private function shortcode_parse_atts($text)
  {
    $atts = array();
    $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
    $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);

    if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER))
    {
      foreach ($match as $m)
      {
        if (!empty($m[1]))
        {
          $atts[strtolower($m[1])] = stripcslashes($m[2]);
        }
        elseif (!empty($m[3]))
        {
          $atts[strtolower($m[3])] = stripcslashes($m[4]);
        }
        elseif (!empty($m[5]))
        {
          $atts[strtolower($m[5])] = stripcslashes($m[6]);
        }
        elseif (isset($m[7]) and strlen($m[7]))
        {
          $atts[] = stripcslashes($m[7]);
        }
        elseif (isset($m[8]))
        {
          $atts[] = stripcslashes($m[8]);
        }
      }
    }
    else
    {
      $atts = ltrim($text);
    }

    return $atts;
  }

  /**
   * Combine user attributes with known attributes and fill in defaults when needed.
   *
   * The pairs should be considered to be all of the attributes which are
   * supported by the caller and given as a list. The returned attributes will
   * only contain the attributes in the $pairs list.
   *
   * The $pairs value can be the default value or an array with:
   *    - [0] validation regex or 'boolean'
   *    - [1] default value
   *
   * If the $atts list has unsupported attributes, then they will be ignored and
   * removed from the final returned list.
   *
   * If the third parameter is present and an Hooks instance is available, then the
   * filter "shortcode_atts_{$shortcode}" will be applied to the returned list.
   *    - array $out   The output array of shortcode attributes.
   *    - array $pairs The supported attributes and their defaults.
   *    - array $atts  The user defined shortcode attributes.
   *
   * @since 0.1
   * @access public
   *
   * @param array  $pairs     Entire list of supported attributes and their defaults.
   * @param array  $atts      User defined attributes in shortcode tag.
   * @param string $shortcode Optional. The name of the shortcode, provided for context to enable filtering
   * @return array Combined and filtered attribute list.
   */
  public function shortcode_atts($pairs, $atts, $shortcode = null)
  {
    $atts = (array)$atts;
    $out = array();

    foreach ($pairs as $name => $default)
    {
      if (is_array($default))
      {
        if (array_key_exists($name, $atts))
        {
          $is_bool = ($default[0] === 'boolean') && ($default[0] = '1|0|yes|no|true|false');

          if (preg_match('/'.$default[0].'/', $atts[$name]))
          {
            if ($is_bool)
            {
              $out[$name] = filter_var($atts[$name], FILTER_VALIDATE_BOOLEAN);
            }
            else
            {
              $out[$name] = $atts[$name];
            }
          }
          else
          {
            $out[$name] = $default[1];
          }
        }
        else
        {
          $out[$name] = $default[1];
        }
      }
      else
      {
        if (array_key_exists($name, $atts))
        {
          $out[$name] = $atts[$name];
        }
        else
        {
          $out[$name] = $default;
        }
      }
    }

    if ($shortcode != null && $this->hooks != null)
    {
      $out = $this->hooks->apply_filters(
        "shortcode_atts_$shortcode",
        $out, $pairs, $atts
      );
    }

    return $out;
  }

  /**
   * Remove all shortcode tags from the given content.
   *
   * @since 0.1
   * @access public
   *
   * @param string $content Content to remove shortcode tags.
   * @return string Content without shortcode tags.
   */
  public function strip_shortcodes($content)
  {
    if (empty($this->shortcode_tags) || !is_array($this->shortcode_tags))
    {
      return $content;
    }

    $pattern = $this->get_shortcode_regex();

    return preg_replace_callback(
      "/$pattern/s",
      array($this, 'strip_shortcode_tag'),
      $content
    );
  }

  /**
   * strip shortcode tag
   *
   * @since 0.1
   * @access private
   *
   * @param $m
   * @return string
   */
  private function strip_shortcode_tag($m)
  {
    // allow [[foo]] syntax for escaping a tag
    if ($m[1] == '[' && $m[6] == ']')
    {
      return substr($m[0], 1, -1);
    }

    return $m[1] . $m[6];
  }

}
