PHP-Hooks
=========

The PHP Hooks Class is a fork of the WordPress filters hook system rolled in to a class to be ported into any php based system. Most (if not all) of the code comes from Wordpress.


----------

# How to Use?

Simple, Include the class file in your application bootstrap (setup/load/configuration or whatever you call it) and start hooking your filter and action hooks using the global `$hooks`. Ex:

```PHP
include_once('Hooks.class.php');
$hooks = new Hooks();

$hooks->add_action('header_action','echo_this_in_header');

function echo_this_in_header() {
   echo 'this came from a hooked function';
}
```

Then all that is left for you is to call the hooked function when you want anywhere in your aplication, EX:

```PHP
echo '<div id="extra_header">';
$hooks->do_action('header_action');
echo '</div>';
```


and you output will be:
```HTML
<div id="extra_header">this came from a hooked function</div>
```

# Methods

## ACTIONS

### add_action
Hooks a function on to a specific action.

* **tag** (string). The name of the action to which the **$function_to_add** is hooked.
* **function_to_add** (Callable). The name of the function you wish to be called.
* **priority** (int, optional). Used to specify the order in which the functions associated with a particular action are executed (default: 50). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
* **include_path** (string, optional). File to include before executing the callback.

### do_action
Execute functions hooked on a specific action hook.

* **tag** (string). The name of the action hook.
* **arg,...** (mixed, optional). Additional arguments which are passed to the functions hooked to the action.
* _Will return null if **$tag** does not exist_.

### do_action_ref_array
Same as **do_action** but takes an array of additional arguments.

### remove_action
Removes a function from a specified action hook.

* **tag** (string). The action hook to which the function to be removed is hooked.
* **function_to_remove** (Callable). The name of the function which should be removed.
* **priority** (int, optional). The priority of the function (default: 50).
* _Will return a boolean whether the function was removed_.

### has_action
Check if any action has been registered for a hook.

* **tag** (string). The name of the action hook.
* **function_to_check** (Callable, optional). If omitted, returns boolean for whether the hook has anything registered. When checking a specific function, the priority of that hook is returned, or false if the function is not attached.
* _Will return a boolean or the hook priority._ When using the **$function_to_check** argument, this function may return a non-boolean value that evaluates to false (e.g.) 0, so use the === operator for testing the return value.

### did_action
Retrieve the number of times an action has been fired.

* **tag** (string). The name of the action hook.
* _Will return an integer._

## FILTERS

### add_filter
Hooks a function or method to a specific filter action.

* **tag** (string). The name of the filter to hook the **$function_to_add** to.
* **function_to_add** (Callable). The name of the function to be called when the filter is applied.
* **priority** (int, optional). Used to specify the order in which the functions associated with a particular action are executed (default: 50). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
* **include_path** (string, option). File to include before executing the callback.

### apply_filters
Call the functions added to a filter hook.

* **tag** (string). The name of the filter hook.
* **value** (mixed). The value on which the filters hooked to are applied on.
* **arg,...** (mixed, optional). Additional variables passed to the functions hooked to **$tag**.
* _Will return the filtered value after all hooked functions are applied to it._

### apply_filters_ref_array
Same as **apply_filters** but take an array of parameters (main value + additional arguments).

### remove_filter
Removes a function from a specified filter hook.

* **tag** (string). The filter hook to which the function to be removed is hooked.
* **function_to_remove** (Callable). The name of the function which should be removed.
* **priority** (int, optional). The priority of the function (default: 50).
* _Will return a boolean whether the function was removed._

### has_filter
Check if any filter has been registered for a hook.

* **tag** (string). The name of the filter hook.
* **function_to_check** (Callable, optional). If omitted, returns boolean for whether the hook has anything registered. When checking a specific function, the priority of that hook is  returned, or false if the function is not attached.
* _Will return a boolean of the hook priority._ When using the **$function_to_check** argument, this function may return a non-boolean value that evaluates to false (e.g.) 0, so use the === operator for testing the return value.

### doing_filter
Check is a filter is currently being processed.

* **tag** (string, optional). The name of the filter hook. If ommited, will return true whether any filter is running.
* _Will return a boolean._

