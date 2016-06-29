<?php

namespace Iubar\Login\Services;

/**
 * Class Filter
 *
 * This is the place to put filters, usually methods that cleans, sorts and, well, filters stuff.
 */
class Filter
{
    /**
     * The XSS filter: This simply removes "code" from any data, used to prevent Cross-Site Scripting Attacks.
     *
     * A very simple introduction: Let's say an attackers changes its username from "John" to these lines:
     * "<script>var http = new XMLHttpRequest(); http.open('POST', 'example.com/my_account/delete.php', true);</script>"
     * This means, every user's browser would render "John" anymore, instead interpreting this JavaScript code, calling
     * the delete.php, in this case inside the project, in worse scenarios something like performing a bank transaction
     * or sending your cookie data (containing your remember-me-token) to somebody else.
     *
     * What is XSS ?
     * @see http://phpsecurity.readthedocs.org/en/latest/Cross-Site-Scripting-%28XSS%29.html
     *
     * Deeper information:
     * @see https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
     *
     * XSSFilter expects a value, checks if the value is a string, and if so, encodes typical script tag chars to
     * harmless HTML (you'll see the code, it wil not be interpreted). Note that this method uses reference to the
     * passed variable, not a copy, meaning you can use this methods like this:
     *
     * CORRECT: Filter::XSSFilter($myVariable);
     * WRONG: $myVariable = Filter::XSSFilter($myVariable);
     *
     * This works like some other popular PHP functions, for example sort().
     * @see http://php.net/manual/en/function.sort.php
     *
     * @see http://stackoverflow.com/questions/1676897/what-does-it-mean-to-start-a-php-function-with-an-ampersand
     * @see http://php.net/manual/en/language.references.pass.php
     *
     * FYI: htmlspecialchars() does this (from PHP docs):
     *
     * '&' (ampersand) becomes '&amp;'
     * '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
     * "'" (single quote) becomes '&#039;' (or &apos;) only when ENT_QUOTES is set.
     * '<' (less than) becomes '&lt;'
     * '>' (greater than) becomes '&gt;'
     *
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     *
     * @param $value
     * @return mixed
     */
    public static function XSSFilter(&$value)
    {
        if (is_string($value)) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
    
    public static function MyXSSFilter($value)
    {
    	if (is_string($value)) {
    		$value = strip_tags($value);
    		$value = htmlentities($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    		// ENT_QUOTES: Will convert both double and single quotes.
    		// ENT_HTML5: Handle code as HTML 5  (also strips newline chars like \n with htmlentities while htmlspecialchars is not affected by that)
    		// oppure potrei usare htmlentities() // http://php.net/manual/it/function.htmlentities.php
    		// oppure strip_tags() http://php.net/manual/en/function.strip-tags.php
    		// http://stackoverflow.com/questions/5788527/is-strip-tags-vulnerable-to-scripting-attacks
    		// http://stackoverflow.com/questions/3623236/htmlspecialchars-vs-htmlentities-when-concerned-with-xss
    		// "You should always use htmlentities and very rarely use htmlspecialchars when sanitizing user input. ALso, you should always strip tags before. And for really important and secure sites, you should NEVER trust strip_tags()"    		
    	}else{
    		throw new \InvalidArgumentException("Il parametro non è una stringa");
    	}
    	return $value;
    }
    
   public static  function html_entity_invert($value) {
    	return html_entity_decode($value, ENT_COMPAT | ENT_HTML5, 'UTF-8');
    }
    
}