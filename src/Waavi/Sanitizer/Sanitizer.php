<?php

namespace Waavi\Sanitizer;

class Sanitizer
{
    /**
     *  Data to sanitize
     *  @var array
     */
    protected $data;

    /**
     *  Filters to apply
     *  @var array
     */
    protected $rules;

    /**
     *  Available filters as $name => $classPath
     *  @var array
     */
    protected $filters;

    /**
     *  Create a new sanitizer instance.
     *
     *  @param  array   $data
     *  @param  array   $rules      Rules to be applied to each data attribute
     *  @param  array   $filters    Available filters for this sanitizer
     *  @return Sanitizer
     */
    public function __construct(array $data, array $rules, array $filters)
    {
        $this->data    = $data;
        $this->rules   = $this->parseRulesArray($rules);
        $this->filters = $filters;
    }

    /**
     *  Parse a rules array.
     *
     *  @param  array $rules
     *  @return array
     */
    protected function parseRulesArray(array $rules)
    {
        $parsedRules = [];
        foreach ($rules as $attribute => $attributeRules) {
            $attributeRulesArray = explode('|', $attributeRules);
            foreach ($attributeRulesArray as $attributeRule) {
                $parsedRule = $this->parseRuleString($attributeRule);
                if ($parsedRule) {
                    $parsedRules[$attribute][] = $parsedRule;
                }
            }
        }
        return $parsedRules;
    }

    /**
     *  Parse a rule string formatted as filterName:option1, option2 into an array formatted as [name => filterName, options => [option1, option2]]
     *
     *  @param  string $rule    Formatted as 'filterName:option1, option2' or just 'filterName'
     *  @return array           Formatted as [name => filterName, options => [option1, option2]]. Empty array if no filter name was found.
     */
    protected function parseRuleString($rule)
    {
        list($name, $options) = explode(':', $rule, 2);
        if (!$name) {
            return [];
        }
        $options = array_map('trim', explode(',', $options));
        return compact('name', 'options');
    }

    /**
     *  Apply the given filter by its name
     *  @param  $name
     *  @return Filter
     */
    protected function applyFilter($name, $value, $options = [])
    {
        // If the filter does not exist, throw an Exception:
        if (!isset($this->filters[$name])) {
            throw new InvalidArgumentException("No filter found by the name of $name");
        }

        // If no value is given, skip the sanitizer
        if (empty($value)) {
            return $value;
        }

        $filter = $this->filters[$name];
        if ($filter instanceof Closure) {
            return call_user_func_array($filter, [$value, $options]);
        } else {
            return $filter->apply($value, $options);
        }
    }

    /**
     *  Sanitize the given data
     *  @return array
     */
    public function sanitize()
    {
        $sanitized = [];
        foreach ($this->data as $name => $value) {
            $sanitized[$name] = $this->sanitizeAttribute($name, $value);
        }
        return $sanitized;
    }

    protected function sanitizeAttribute($attribute, $value)
    {
        if (!isset($this->rules[$attribute])) {
            return $value;
        } else {
            foreach ($this->rules[$attributes] as $rule) {
                $value = $this->applyFilter($rule['name'], $value, $rule['options']);
            }
        }
    }
}
