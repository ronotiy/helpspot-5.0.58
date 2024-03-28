/*  Prototype JavaScript framework, version 1.7
 *  (c) 2005-2010 Sam Stephenson
 *
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://www.prototypejs.org/
 *
 *--------------------------------------------------------------------------*/

var Prototype = {

  Version: '1.7',

  Browser: (function(){
    var ua = navigator.userAgent;
    var isOpera = Object.prototype.toString.call(window.opera) == '[object Opera]';
    return {
      IE:             !!window.attachEvent && !isOpera,
      Opera:          isOpera,
      WebKit:         ua.indexOf('AppleWebKit/') > -1,
      Gecko:          ua.indexOf('Gecko') > -1 && ua.indexOf('KHTML') === -1,
      MobileSafari:   /Apple.*Mobile/.test(ua)
    }
  })(),

  BrowserFeatures: {
    XPath: !!document.evaluate,

    SelectorsAPI: !!document.querySelector,

    ElementExtensions: (function() {
      var constructor = window.Element || window.HTMLElement;
      return !!(constructor && constructor.prototype);
    })(),
    SpecificElementExtensions: (function() {
      if (typeof window.HTMLDivElement !== 'undefined')
        return true;

      var div = document.createElement('div'),
          form = document.createElement('form'),
          isSupported = false;

      if (div['__proto__'] && (div['__proto__'] !== form['__proto__'])) {
        isSupported = true;
      }

      div = form = null;

      return isSupported;
    })()
  },

  ScriptFragment: '<script[^>]*>([\\S\\s]*?)<\/script>',
  JSONFilter: /^\/\*-secure-([\s\S]*)\*\/\s*$/,

  emptyFunction: function() { },

  K: function(x) { return x }
};

if (Prototype.Browser.MobileSafari)
  Prototype.BrowserFeatures.SpecificElementExtensions = false;
/* Based on Alex Arnell's inheritance implementation. */

var Class = (function() {

  var IS_DONTENUM_BUGGY = (function(){
    for (var p in { toString: 1 }) {
      if (p === 'toString') return false;
    }
    return true;
  })();

  function subclass() {};
  function create() {
    var parent = null, properties = $A(arguments);
    if (Object.isFunction(properties[0]))
      parent = properties.shift();

    function klass() {
      this.initialize.apply(this, arguments);
    }

    Object.extend(klass, Class.Methods);
    klass.superclass = parent;
    klass.subclasses = [];

    if (parent) {
      subclass.prototype = parent.prototype;
      klass.prototype = new subclass;
      parent.subclasses.push(klass);
    }

    for (var i = 0, length = properties.length; i < length; i++)
      klass.addMethods(properties[i]);

    if (!klass.prototype.initialize)
      klass.prototype.initialize = Prototype.emptyFunction;

    klass.prototype.constructor = klass;
    return klass;
  }

  function addMethods(source) {
    var ancestor   = this.superclass && this.superclass.prototype,
        properties = Object.keys(source);

    if (IS_DONTENUM_BUGGY) {
      if (source.toString != Object.prototype.toString)
        properties.push("toString");
      if (source.valueOf != Object.prototype.valueOf)
        properties.push("valueOf");
    }

    for (var i = 0, length = properties.length; i < length; i++) {
      var property = properties[i], value = source[property];
      if (ancestor && Object.isFunction(value) &&
          value.argumentNames()[0] == "$super") {
        var method = value;
        value = (function(m) {
          return function() { return ancestor[m].apply(this, arguments); };
        })(property).wrap(method);

        value.valueOf = method.valueOf.bind(method);
        value.toString = method.toString.bind(method);
      }
      this.prototype[property] = value;
    }

    return this;
  }

  return {
    create: create,
    Methods: {
      addMethods: addMethods
    }
  };
})();
(function() {

  var _toString = Object.prototype.toString,
      NULL_TYPE = 'Null',
      UNDEFINED_TYPE = 'Undefined',
      BOOLEAN_TYPE = 'Boolean',
      NUMBER_TYPE = 'Number',
      STRING_TYPE = 'String',
      OBJECT_TYPE = 'Object',
      FUNCTION_CLASS = '[object Function]',
      BOOLEAN_CLASS = '[object Boolean]',
      NUMBER_CLASS = '[object Number]',
      STRING_CLASS = '[object String]',
      ARRAY_CLASS = '[object Array]',
      DATE_CLASS = '[object Date]',
      NATIVE_JSON_STRINGIFY_SUPPORT = window.JSON &&
        typeof JSON.stringify === 'function' &&
        JSON.stringify(0) === '0' &&
        typeof JSON.stringify(Prototype.K) === 'undefined';

  function Type(o) {
    switch(o) {
      case null: return NULL_TYPE;
      case (void 0): return UNDEFINED_TYPE;
    }
    var type = typeof o;
    switch(type) {
      case 'boolean': return BOOLEAN_TYPE;
      case 'number':  return NUMBER_TYPE;
      case 'string':  return STRING_TYPE;
    }
    return OBJECT_TYPE;
  }

  function extend(destination, source) {
    for (var property in source)
      destination[property] = source[property];
    return destination;
  }

  function inspect(object) {
    try {
      if (isUndefined(object)) return 'undefined';
      if (object === null) return 'null';
      return object.inspect ? object.inspect() : String(object);
    } catch (e) {
      if (e instanceof RangeError) return '...';
      throw e;
    }
  }

  function toJSON(value) {
    return Str('', { '': value }, []);
  }

  function Str(key, holder, stack) {
    var value = holder[key],
        type = typeof value;

    if (Type(value) === OBJECT_TYPE && typeof value.toJSON === 'function') {
      value = value.toJSON(key);
    }

    var _class = _toString.call(value);

    switch (_class) {
      case NUMBER_CLASS:
      case BOOLEAN_CLASS:
      case STRING_CLASS:
        value = value.valueOf();
    }

    switch (value) {
      case null: return 'null';
      case true: return 'true';
      case false: return 'false';
    }

    type = typeof value;
    switch (type) {
      case 'string':
        return value.inspect(true);
      case 'number':
        return isFinite(value) ? String(value) : 'null';
      case 'object':

        for (var i = 0, length = stack.length; i < length; i++) {
          if (stack[i] === value) { throw new TypeError(); }
        }
        stack.push(value);

        var partial = [];
        if (_class === ARRAY_CLASS) {
          for (var i = 0, length = value.length; i < length; i++) {
            var str = Str(i, value, stack);
            partial.push(typeof str === 'undefined' ? 'null' : str);
          }
          partial = '[' + partial.join(',') + ']';
        } else {
          var keys = Object.keys(value);
          for (var i = 0, length = keys.length; i < length; i++) {
            var key = keys[i], str = Str(key, value, stack);
            if (typeof str !== "undefined") {
               partial.push(key.inspect(true)+ ':' + str);
             }
          }
          partial = '{' + partial.join(',') + '}';
        }
        stack.pop();
        return partial;
    }
  }

  function stringify(object) {
    return JSON.stringify(object);
  }

  function toQueryString(object) {
    return $H(object).toQueryString();
  }

  function toHTML(object) {
    return object && object.toHTML ? object.toHTML() : String.interpret(object);
  }

  function keys(object) {
    if (Type(object) !== OBJECT_TYPE) { throw new TypeError(); }
    var results = [];
    for (var property in object) {
      if (object.hasOwnProperty(property)) {
        results.push(property);
      }
    }
    return results;
  }

  function values(object) {
    var results = [];
    for (var property in object)
      results.push(object[property]);
    return results;
  }

  function clone(object) {
    return extend({ }, object);
  }

  function isElement(object) {
    return !!(object && object.nodeType == 1);
  }

  function isArray(object) {
    return _toString.call(object) === ARRAY_CLASS;
  }

  var hasNativeIsArray = (typeof Array.isArray == 'function')
    && Array.isArray([]) && !Array.isArray({});

  if (hasNativeIsArray) {
    isArray = Array.isArray;
  }

  function isHash(object) {
    return object instanceof Hash;
  }

  function isFunction(object) {
    return _toString.call(object) === FUNCTION_CLASS;
  }

  function isString(object) {
    return _toString.call(object) === STRING_CLASS;
  }

  function isNumber(object) {
    return _toString.call(object) === NUMBER_CLASS;
  }

  function isDate(object) {
    return _toString.call(object) === DATE_CLASS;
  }

  function isUndefined(object) {
    return typeof object === "undefined";
  }

  extend(Object, {
    extend:        extend,
    inspect:       inspect,
    toJSON:        NATIVE_JSON_STRINGIFY_SUPPORT ? stringify : toJSON,
    toQueryString: toQueryString,
    toHTML:        toHTML,
    keys:          Object.keys || keys,
    values:        values,
    clone:         clone,
    isElement:     isElement,
    isArray:       isArray,
    isHash:        isHash,
    isFunction:    isFunction,
    isString:      isString,
    isNumber:      isNumber,
    isDate:        isDate,
    isUndefined:   isUndefined
  });
})();
Object.extend(Function.prototype, (function() {
  var slice = Array.prototype.slice;

  function update(array, args) {
    var arrayLength = array.length, length = args.length;
    while (length--) array[arrayLength + length] = args[length];
    return array;
  }

  function merge(array, args) {
    array = slice.call(array, 0);
    return update(array, args);
  }

  function argumentNames() {
    var names = this.toString().match(/^[\s\(]*function[^(]*\(([^)]*)\)/)[1]
      .replace(/\/\/.*?[\r\n]|\/\*(?:.|[\r\n])*?\*\//g, '')
      .replace(/\s+/g, '').split(',');
    return names.length == 1 && !names[0] ? [] : names;
  }

  function bind(context) {
    if (arguments.length < 2 && Object.isUndefined(arguments[0])) return this;
    var __method = this, args = slice.call(arguments, 1);
    return function() {
      var a = merge(args, arguments);
      return __method.apply(context, a);
    }
  }

  function bindAsEventListener(context) {
    var __method = this, args = slice.call(arguments, 1);
    return function(event) {
      var a = update([event || window.event], args);
      return __method.apply(context, a);
    }
  }

  function curry() {
    if (!arguments.length) return this;
    var __method = this, args = slice.call(arguments, 0);
    return function() {
      var a = merge(args, arguments);
      return __method.apply(this, a);
    }
  }

  function delay(timeout) {
    var __method = this, args = slice.call(arguments, 1);
    timeout = timeout * 1000;
    return window.setTimeout(function() {
      return __method.apply(__method, args);
    }, timeout);
  }

  function defer() {
    var args = update([0.01], arguments);
    return this.delay.apply(this, args);
  }

  function wrap(wrapper) {
    var __method = this;
    return function() {
      var a = update([__method.bind(this)], arguments);
      return wrapper.apply(this, a);
    }
  }

  function methodize() {
    if (this._methodized) return this._methodized;
    var __method = this;
    return this._methodized = function() {
      var a = update([this], arguments);
      return __method.apply(null, a);
    };
  }

  return {
    argumentNames:       argumentNames,
    bind:                bind,
    bindAsEventListener: bindAsEventListener,
    curry:               curry,
    delay:               delay,
    defer:               defer,
    wrap:                wrap,
    methodize:           methodize
  }
})());



(function(proto) {


  function toISOString() {
    return this.getUTCFullYear() + '-' +
      (this.getUTCMonth() + 1).toPaddedString(2) + '-' +
      this.getUTCDate().toPaddedString(2) + 'T' +
      this.getUTCHours().toPaddedString(2) + ':' +
      this.getUTCMinutes().toPaddedString(2) + ':' +
      this.getUTCSeconds().toPaddedString(2) + 'Z';
  }


  function toJSON() {
    return this.toISOString();
  }

  if (!proto.toISOString) proto.toISOString = toISOString;
  if (!proto.toJSON) proto.toJSON = toJSON;

})(Date.prototype);


RegExp.prototype.match = RegExp.prototype.test;

RegExp.escape = function(str) {
  return String(str).replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
};
var PeriodicalExecuter = Class.create({
  initialize: function(callback, frequency) {
    this.callback = callback;
    this.frequency = frequency;
    this.currentlyExecuting = false;

    this.registerCallback();
  },

  registerCallback: function() {
    this.timer = setInterval(this.onTimerEvent.bind(this), this.frequency * 1000);
  },

  execute: function() {
    this.callback(this);
  },

  stop: function() {
    if (!this.timer) return;
    clearInterval(this.timer);
    this.timer = null;
  },

  onTimerEvent: function() {
    if (!this.currentlyExecuting) {
      try {
        this.currentlyExecuting = true;
        this.execute();
        this.currentlyExecuting = false;
      } catch(e) {
        this.currentlyExecuting = false;
        throw e;
      }
    }
  }
});
Object.extend(String, {
  interpret: function(value) {
    return value == null ? '' : String(value);
  },
  specialChar: {
    '\b': '\\b',
    '\t': '\\t',
    '\n': '\\n',
    '\f': '\\f',
    '\r': '\\r',
    '\\': '\\\\'
  }
});

Object.extend(String.prototype, (function() {
  var NATIVE_JSON_PARSE_SUPPORT = window.JSON &&
    typeof JSON.parse === 'function' &&
    JSON.parse('{"test": true}').test;

  function prepareReplacement(replacement) {
    if (Object.isFunction(replacement)) return replacement;
    var template = new Template(replacement);
    return function(match) { return template.evaluate(match) };
  }

  function gsub(pattern, replacement) {
    var result = '', source = this, match;
    replacement = prepareReplacement(replacement);

    if (Object.isString(pattern))
      pattern = RegExp.escape(pattern);

    if (!(pattern.length || pattern.source)) {
      replacement = replacement('');
      return replacement + source.split('').join(replacement) + replacement;
    }

    while (source.length > 0) {
      if (match = source.match(pattern)) {
        result += source.slice(0, match.index);
        result += String.interpret(replacement(match));
        source  = source.slice(match.index + match[0].length);
      } else {
        result += source, source = '';
      }
    }
    return result;
  }

  function sub(pattern, replacement, count) {
    replacement = prepareReplacement(replacement);
    count = Object.isUndefined(count) ? 1 : count;

    return this.gsub(pattern, function(match) {
      if (--count < 0) return match[0];
      return replacement(match);
    });
  }

  function scan(pattern, iterator) {
    this.gsub(pattern, iterator);
    return String(this);
  }

  function truncate(length, truncation) {
    length = length || 30;
    truncation = Object.isUndefined(truncation) ? '...' : truncation;
    return this.length > length ?
      this.slice(0, length - truncation.length) + truncation : String(this);
  }

  function strip() {
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
  }

  function stripTags() {
    return this.replace(/<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?>|<\/\w+>/gi, '');
  }

  function stripScripts() {
    return this.replace(new RegExp(Prototype.ScriptFragment, 'img'), '');
  }

  function extractScripts() {
    var matchAll = new RegExp(Prototype.ScriptFragment, 'img'),
        matchOne = new RegExp(Prototype.ScriptFragment, 'im');
    return (this.match(matchAll) || []).map(function(scriptTag) {
      return (scriptTag.match(matchOne) || ['', ''])[1];
    });
  }

  function evalScripts() {
    return this.extractScripts().map(function(script) { return eval(script) });
  }

  function escapeHTML() {
    return this.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function unescapeHTML() {
    return this.stripTags().replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');
  }


  function toQueryParams(separator) {
    var match = this.strip().match(/([^?#]*)(#.*)?$/);
    if (!match) return { };

    return match[1].split(separator || '&').inject({ }, function(hash, pair) {
      if ((pair = pair.split('='))[0]) {
        var key = decodeURIComponent(pair.shift()),
            value = pair.length > 1 ? pair.join('=') : pair[0];

        if (value != undefined) value = decodeURIComponent(value);

        if (key in hash) {
          if (!Object.isArray(hash[key])) hash[key] = [hash[key]];
          hash[key].push(value);
        }
        else hash[key] = value;
      }
      return hash;
    });
  }

  function toArray() {
    return this.split('');
  }

  function succ() {
    return this.slice(0, this.length - 1) +
      String.fromCharCode(this.charCodeAt(this.length - 1) + 1);
  }

  function times(count) {
    return count < 1 ? '' : new Array(count + 1).join(this);
  }

  function camelize() {
    return this.replace(/-+(.)?/g, function(match, chr) {
      return chr ? chr.toUpperCase() : '';
    });
  }

  function capitalize() {
    return this.charAt(0).toUpperCase() + this.substring(1).toLowerCase();
  }

  function underscore() {
    return this.replace(/::/g, '/')
               .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
               .replace(/([a-z\d])([A-Z])/g, '$1_$2')
               .replace(/-/g, '_')
               .toLowerCase();
  }

  function dasherize() {
    return this.replace(/_/g, '-');
  }

  function inspect(useDoubleQuotes) {
    var escapedString = this.replace(/[\x00-\x1f\\]/g, function(character) {
      if (character in String.specialChar) {
        return String.specialChar[character];
      }
      return '\\u00' + character.charCodeAt().toPaddedString(2, 16);
    });
    if (useDoubleQuotes) return '"' + escapedString.replace(/"/g, '\\"') + '"';
    return "'" + escapedString.replace(/'/g, '\\\'') + "'";
  }

  function unfilterJSON(filter) {
    return this.replace(filter || Prototype.JSONFilter, '$1');
  }

  function isJSON() {
    var str = this;
    if (str.blank()) return false;
    str = str.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@');
    str = str.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
    str = str.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
    return (/^[\],:{}\s]*$/).test(str);
  }

  function evalJSON(sanitize) {
    var json = this.unfilterJSON(),
        cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;
    if (cx.test(json)) {
      json = json.replace(cx, function (a) {
        return '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
      });
    }
    try {
      if (!sanitize || json.isJSON()) return eval('(' + json + ')');
    } catch (e) { }
    throw new SyntaxError('Badly formed JSON string: ' + this.inspect());
  }

  function parseJSON() {
    var json = this.unfilterJSON();
    return JSON.parse(json);
  }

  function include(pattern) {
    return this.indexOf(pattern) > -1;
  }

  function startsWith(pattern) {
    return this.lastIndexOf(pattern, 0) === 0;
  }

  function endsWith(pattern) {
    var d = this.length - pattern.length;
    return d >= 0 && this.indexOf(pattern, d) === d;
  }

  function empty() {
    return this == '';
  }

  function blank() {
    return /^\s*$/.test(this);
  }

  function interpolate(object, pattern) {
    return new Template(this, pattern).evaluate(object);
  }

  return {
    gsub:           gsub,
    sub:            sub,
    scan:           scan,
    truncate:       truncate,
    strip:          String.prototype.trim || strip,
    stripTags:      stripTags,
    stripScripts:   stripScripts,
    extractScripts: extractScripts,
    evalScripts:    evalScripts,
    escapeHTML:     escapeHTML,
    unescapeHTML:   unescapeHTML,
    toQueryParams:  toQueryParams,
    parseQuery:     toQueryParams,
    toArray:        toArray,
    succ:           succ,
    times:          times,
    camelize:       camelize,
    capitalize:     capitalize,
    underscore:     underscore,
    dasherize:      dasherize,
    inspect:        inspect,
    unfilterJSON:   unfilterJSON,
    isJSON:         isJSON,
    evalJSON:       NATIVE_JSON_PARSE_SUPPORT ? parseJSON : evalJSON,
    include:        include,
    startsWith:     startsWith,
    endsWith:       endsWith,
    empty:          empty,
    blank:          blank,
    interpolate:    interpolate
  };
})());

var Template = Class.create({
  initialize: function(template, pattern) {
    this.template = template.toString();
    this.pattern = pattern || Template.Pattern;
  },

  evaluate: function(object) {
    if (object && Object.isFunction(object.toTemplateReplacements))
      object = object.toTemplateReplacements();

    return this.template.gsub(this.pattern, function(match) {
      if (object == null) return (match[1] + '');

      var before = match[1] || '';
      if (before == '\\') return match[2];

      var ctx = object, expr = match[3],
          pattern = /^([^.[]+|\[((?:.*?[^\\])?)\])(\.|\[|$)/;

      match = pattern.exec(expr);
      if (match == null) return before;

      while (match != null) {
        var comp = match[1].startsWith('[') ? match[2].replace(/\\\\]/g, ']') : match[1];
        ctx = ctx[comp];
        if (null == ctx || '' == match[3]) break;
        expr = expr.substring('[' == match[3] ? match[1].length : match[0].length);
        match = pattern.exec(expr);
      }

      return before + String.interpret(ctx);
    });
  }
});
Template.Pattern = /(^|.|\r|\n)(#\{(.*?)\})/;

var $break = { };

var Enumerable = (function() {
  function each(iterator, context) {
    var index = 0;
    try {
      this._each(function(value) {
        iterator.call(context, value, index++);
      });
    } catch (e) {
      if (e != $break) throw e;
    }
    return this;
  }

  function eachSlice(number, iterator, context) {
    var index = -number, slices = [], array = this.toArray();
    if (number < 1) return array;
    while ((index += number) < array.length)
      slices.push(array.slice(index, index+number));
    return slices.collect(iterator, context);
  }

  function all(iterator, context) {
    iterator = iterator || Prototype.K;
    var result = true;
    this.each(function(value, index) {
      result = result && !!iterator.call(context, value, index);
      if (!result) throw $break;
    });
    return result;
  }

  function any(iterator, context) {
    iterator = iterator || Prototype.K;
    var result = false;
    this.each(function(value, index) {
      if (result = !!iterator.call(context, value, index))
        throw $break;
    });
    return result;
  }

  function collect(iterator, context) {
    iterator = iterator || Prototype.K;
    var results = [];
    this.each(function(value, index) {
      results.push(iterator.call(context, value, index));
    });
    return results;
  }

  function detect(iterator, context) {
    var result;
    this.each(function(value, index) {
      if (iterator.call(context, value, index)) {
        result = value;
        throw $break;
      }
    });
    return result;
  }

  function findAll(iterator, context) {
    var results = [];
    this.each(function(value, index) {
      if (iterator.call(context, value, index))
        results.push(value);
    });
    return results;
  }

  function grep(filter, iterator, context) {
    iterator = iterator || Prototype.K;
    var results = [];

    if (Object.isString(filter))
      filter = new RegExp(RegExp.escape(filter));

    this.each(function(value, index) {
      if (filter.match(value))
        results.push(iterator.call(context, value, index));
    });
    return results;
  }

  function include(object) {
    if (Object.isFunction(this.indexOf))
      if (this.indexOf(object) != -1) return true;

    var found = false;
    this.each(function(value) {
      if (value == object) {
        found = true;
        throw $break;
      }
    });
    return found;
  }

  function inGroupsOf(number, fillWith) {
    fillWith = Object.isUndefined(fillWith) ? null : fillWith;
    return this.eachSlice(number, function(slice) {
      while(slice.length < number) slice.push(fillWith);
      return slice;
    });
  }

  function inject(memo, iterator, context) {
    this.each(function(value, index) {
      memo = iterator.call(context, memo, value, index);
    });
    return memo;
  }

  function invoke(method) {
    var args = $A(arguments).slice(1);
    return this.map(function(value) {
      return value[method].apply(value, args);
    });
  }

  function max(iterator, context) {
    iterator = iterator || Prototype.K;
    var result;
    this.each(function(value, index) {
      value = iterator.call(context, value, index);
      if (result == null || value >= result)
        result = value;
    });
    return result;
  }

  function min(iterator, context) {
    iterator = iterator || Prototype.K;
    var result;
    this.each(function(value, index) {
      value = iterator.call(context, value, index);
      if (result == null || value < result)
        result = value;
    });
    return result;
  }

  function partition(iterator, context) {
    iterator = iterator || Prototype.K;
    var trues = [], falses = [];
    this.each(function(value, index) {
      (iterator.call(context, value, index) ?
        trues : falses).push(value);
    });
    return [trues, falses];
  }

  function pluck(property) {
    var results = [];
    this.each(function(value) {
      results.push(value[property]);
    });
    return results;
  }

  function reject(iterator, context) {
    var results = [];
    this.each(function(value, index) {
      if (!iterator.call(context, value, index))
        results.push(value);
    });
    return results;
  }

  function sortBy(iterator, context) {
    return this.map(function(value, index) {
      return {
        value: value,
        criteria: iterator.call(context, value, index)
      };
    }).sort(function(left, right) {
      var a = left.criteria, b = right.criteria;
      return a < b ? -1 : a > b ? 1 : 0;
    }).pluck('value');
  }

  function toArray() {
    return this.map();
  }

  function zip() {
    var iterator = Prototype.K, args = $A(arguments);
    if (Object.isFunction(args.last()))
      iterator = args.pop();

    var collections = [this].concat(args).map($A);
    return this.map(function(value, index) {
      return iterator(collections.pluck(index));
    });
  }

  function size() {
    return this.toArray().length;
  }

  function inspect() {
    return '#<Enumerable:' + this.toArray().inspect() + '>';
  }









  return {
    each:       each,
    eachSlice:  eachSlice,
    all:        all,
    every:      all,
    any:        any,
    some:       any,
    collect:    collect,
    map:        collect,
    detect:     detect,
    findAll:    findAll,
    select:     findAll,
    filter:     findAll,
    grep:       grep,
    include:    include,
    member:     include,
    inGroupsOf: inGroupsOf,
    inject:     inject,
    invoke:     invoke,
    max:        max,
    min:        min,
    partition:  partition,
    pluck:      pluck,
    reject:     reject,
    sortBy:     sortBy,
    toArray:    toArray,
    entries:    toArray,
    zip:        zip,
    size:       size,
    inspect:    inspect,
    find:       detect
  };
})();

function $A(iterable) {
  if (!iterable) return [];
  if ('toArray' in Object(iterable)) return iterable.toArray();
  var length = iterable.length || 0, results = new Array(length);
  while (length--) results[length] = iterable[length];
  return results;
}


function $w(string) {
  if (!Object.isString(string)) return [];
  string = string.strip();
  return string ? string.split(/\s+/) : [];
}

Array.from = $A;


(function() {
  var arrayProto = Array.prototype,
      slice = arrayProto.slice,
      _each = arrayProto.forEach; // use native browser JS 1.6 implementation if available

  function each(iterator, context) {
    for (var i = 0, length = this.length >>> 0; i < length; i++) {
      if (i in this) iterator.call(context, this[i], i, this);
    }
  }
  if (!_each) _each = each;

  function clear() {
    this.length = 0;
    return this;
  }

  function first() {
    return this[0];
  }

  function last() {
    return this[this.length - 1];
  }

  function compact() {
    return this.select(function(value) {
      return value != null;
    });
  }

  function flatten() {
    return this.inject([], function(array, value) {
      if (Object.isArray(value))
        return array.concat(value.flatten());
      array.push(value);
      return array;
    });
  }

  function without() {
    var values = slice.call(arguments, 0);
    return this.select(function(value) {
      return !values.include(value);
    });
  }

  function reverse(inline) {
    return (inline === false ? this.toArray() : this)._reverse();
  }

  function uniq(sorted) {
    return this.inject([], function(array, value, index) {
      if (0 == index || (sorted ? array.last() != value : !array.include(value)))
        array.push(value);
      return array;
    });
  }

  function intersect(array) {
    return this.uniq().findAll(function(item) {
      return array.detect(function(value) { return item === value });
    });
  }


  function clone() {
    return slice.call(this, 0);
  }

  function size() {
    return this.length;
  }

  function inspect() {
    return '[' + this.map(Object.inspect).join(', ') + ']';
  }

  function indexOf(item, i) {
    i || (i = 0);
    var length = this.length;
    if (i < 0) i = length + i;
    for (; i < length; i++)
      if (this[i] === item) return i;
    return -1;
  }

  function lastIndexOf(item, i) {
    i = isNaN(i) ? this.length : (i < 0 ? this.length + i : i) + 1;
    var n = this.slice(0, i).reverse().indexOf(item);
    return (n < 0) ? n : i - n - 1;
  }

  function concat() {
    var array = slice.call(this, 0), item;
    for (var i = 0, length = arguments.length; i < length; i++) {
      item = arguments[i];
      if (Object.isArray(item) && !('callee' in item)) {
        for (var j = 0, arrayLength = item.length; j < arrayLength; j++)
          array.push(item[j]);
      } else {
        array.push(item);
      }
    }
    return array;
  }

  Object.extend(arrayProto, Enumerable);

  if (!arrayProto._reverse)
    arrayProto._reverse = arrayProto.reverse;

  Object.extend(arrayProto, {
    _each:     _each,
    clear:     clear,
    first:     first,
    last:      last,
    compact:   compact,
    flatten:   flatten,
    without:   without,
    reverse:   reverse,
    uniq:      uniq,
    intersect: intersect,
    clone:     clone,
    toArray:   clone,
    size:      size,
    inspect:   inspect
  });

  var CONCAT_ARGUMENTS_BUGGY = (function() {
    return [].concat(arguments)[0][0] !== 1;
  })(1,2)

  if (CONCAT_ARGUMENTS_BUGGY) arrayProto.concat = concat;

  if (!arrayProto.indexOf) arrayProto.indexOf = indexOf;
  if (!arrayProto.lastIndexOf) arrayProto.lastIndexOf = lastIndexOf;
})();
function $H(object) {
  return new Hash(object);
};

var Hash = Class.create(Enumerable, (function() {
  function initialize(object) {
    this._object = Object.isHash(object) ? object.toObject() : Object.clone(object);
  }


  function _each(iterator) {
    for (var key in this._object) {
      var value = this._object[key], pair = [key, value];
      pair.key = key;
      pair.value = value;
      iterator(pair);
    }
  }

  function set(key, value) {
    return this._object[key] = value;
  }

  function get(key) {
    if (this._object[key] !== Object.prototype[key])
      return this._object[key];
  }

  function unset(key) {
    var value = this._object[key];
    delete this._object[key];
    return value;
  }

  function toObject() {
    return Object.clone(this._object);
  }



  function keys() {
    return this.pluck('key');
  }

  function values() {
    return this.pluck('value');
  }

  function index(value) {
    var match = this.detect(function(pair) {
      return pair.value === value;
    });
    return match && match.key;
  }

  function merge(object) {
    return this.clone().update(object);
  }

  function update(object) {
    return new Hash(object).inject(this, function(result, pair) {
      result.set(pair.key, pair.value);
      return result;
    });
  }

  function toQueryPair(key, value) {
    if (Object.isUndefined(value)) return key;
    return key + '=' + encodeURIComponent(String.interpret(value));
  }

  function toQueryString() {
    return this.inject([], function(results, pair) {
      var key = encodeURIComponent(pair.key), values = pair.value;

      if (values && typeof values == 'object') {
        if (Object.isArray(values)) {
          var queryValues = [];
          for (var i = 0, len = values.length, value; i < len; i++) {
            value = values[i];
            queryValues.push(toQueryPair(key, value));
          }
          return results.concat(queryValues);
        }
      } else results.push(toQueryPair(key, values));
      return results;
    }).join('&');
  }

  function inspect() {
    return '#<Hash:{' + this.map(function(pair) {
      return pair.map(Object.inspect).join(': ');
    }).join(', ') + '}>';
  }

  function clone() {
    return new Hash(this);
  }

  return {
    initialize:             initialize,
    _each:                  _each,
    set:                    set,
    get:                    get,
    unset:                  unset,
    toObject:               toObject,
    toTemplateReplacements: toObject,
    keys:                   keys,
    values:                 values,
    index:                  index,
    merge:                  merge,
    update:                 update,
    toQueryString:          toQueryString,
    inspect:                inspect,
    toJSON:                 toObject,
    clone:                  clone
  };
})());

Hash.from = $H;
Object.extend(Number.prototype, (function() {
  function toColorPart() {
    return this.toPaddedString(2, 16);
  }

  function succ() {
    return this + 1;
  }

  function times(iterator, context) {
    $R(0, this, true).each(iterator, context);
    return this;
  }

  function toPaddedString(length, radix) {
    var string = this.toString(radix || 10);
    return '0'.times(length - string.length) + string;
  }

  function abs() {
    return Math.abs(this);
  }

  function round() {
    return Math.round(this);
  }

  function ceil() {
    return Math.ceil(this);
  }

  function floor() {
    return Math.floor(this);
  }

  return {
    toColorPart:    toColorPart,
    succ:           succ,
    times:          times,
    toPaddedString: toPaddedString,
    abs:            abs,
    round:          round,
    ceil:           ceil,
    floor:          floor
  };
})());

function $R(start, end, exclusive) {
  return new ObjectRange(start, end, exclusive);
}

var ObjectRange = Class.create(Enumerable, (function() {
  function initialize(start, end, exclusive) {
    this.start = start;
    this.end = end;
    this.exclusive = exclusive;
  }

  function _each(iterator) {
    var value = this.start;
    while (this.include(value)) {
      iterator(value);
      value = value.succ();
    }
  }

  function include(value) {
    if (value < this.start)
      return false;
    if (this.exclusive)
      return value < this.end;
    return value <= this.end;
  }

  return {
    initialize: initialize,
    _each:      _each,
    include:    include
  };
})());



var Abstract = { };


var Try = {
  these: function() {
    var returnValue;

    for (var i = 0, length = arguments.length; i < length; i++) {
      var lambda = arguments[i];
      try {
        returnValue = lambda();
        break;
      } catch (e) { }
    }

    return returnValue;
  }
};

var Ajax = {
  getTransport: function() {
    return Try.these(
      function() {return new XMLHttpRequest()},
      function() {return new ActiveXObject('Msxml2.XMLHTTP')},
      function() {return new ActiveXObject('Microsoft.XMLHTTP')}
    ) || false;
  },

  activeRequestCount: 0
};

Ajax.Responders = {
  responders: [],

  _each: function(iterator) {
    this.responders._each(iterator);
  },

  register: function(responder) {
    if (!this.include(responder))
      this.responders.push(responder);
  },

  unregister: function(responder) {
    this.responders = this.responders.without(responder);
  },

  dispatch: function(callback, request, transport, json) {
    this.each(function(responder) {
      if (Object.isFunction(responder[callback])) {
        try {
          responder[callback].apply(responder, [request, transport, json]);
        } catch (e) { }
      }
    });
  }
};

Object.extend(Ajax.Responders, Enumerable);

Ajax.Responders.register({
  onCreate:   function() { Ajax.activeRequestCount++ },
  onComplete: function() { Ajax.activeRequestCount-- }
});
Ajax.Base = Class.create({
  initialize: function(options) {
    this.options = {
      method:       'post',
      asynchronous: true,
      contentType:  'application/x-www-form-urlencoded',
      encoding:     'UTF-8',
      parameters:   '',
      evalJSON:     true,
      evalJS:       true
    };
    Object.extend(this.options, options || { });

    this.options.method = this.options.method.toLowerCase();

    if (Object.isHash(this.options.parameters))
      this.options.parameters = this.options.parameters.toObject();
  }
});
Ajax.Request = Class.create(Ajax.Base, {
  _complete: false,

  initialize: function($super, url, options) {
    $super(options);
    this.transport = Ajax.getTransport();
    this.request(url);
  },

  request: function(url) {
    this.url = url;
    this.method = this.options.method;
    var params = Object.isString(this.options.parameters) ?
          this.options.parameters :
          Object.toQueryString(this.options.parameters);

    if (!['get', 'post'].include(this.method)) {
      params += (params ? '&' : '') + "_method=" + this.method;
      this.method = 'post';
    }

    if (params && this.method === 'get') {
      this.url += (this.url.include('?') ? '&' : '?') + params;
    }

    this.parameters = params.toQueryParams();

    try {
      var response = new Ajax.Response(this);
      if (this.options.onCreate) this.options.onCreate(response);
      Ajax.Responders.dispatch('onCreate', this, response);

      this.transport.open(this.method.toUpperCase(), this.url,
        this.options.asynchronous);

      if (this.options.asynchronous) this.respondToReadyState.bind(this).defer(1);

      this.transport.onreadystatechange = this.onStateChange.bind(this);
      this.setRequestHeaders();

      this.body = this.method == 'post' ? (this.options.postBody || params) : null;
      this.transport.send(this.body);

      /* Force Firefox to handle ready state 4 for synchronous requests */
      if (!this.options.asynchronous && this.transport.overrideMimeType)
        this.onStateChange();

    }
    catch (e) {
      this.dispatchException(e);
    }
  },

  onStateChange: function() {
    var readyState = this.transport.readyState;
    if (readyState > 1 && !((readyState == 4) && this._complete))
      this.respondToReadyState(this.transport.readyState);
  },

  setRequestHeaders: function() {
    var headers = {
      'X-Requested-With': 'XMLHttpRequest',
      'X-Prototype-Version': Prototype.Version,
      'Accept': 'text/javascript, text/html, application/xml, text/xml, */*'
    };

    if (this.method == 'post') {
      headers['Content-type'] = this.options.contentType +
        (this.options.encoding ? '; charset=' + this.options.encoding : '');

      /* Force "Connection: close" for older Mozilla browsers to work
       * around a bug where XMLHttpRequest sends an incorrect
       * Content-length header. See Mozilla Bugzilla #246651.
       */
      if (this.transport.overrideMimeType &&
          (navigator.userAgent.match(/Gecko\/(\d{4})/) || [0,2005])[1] < 2005)
            headers['Connection'] = 'close';
    }

    if (typeof this.options.requestHeaders == 'object') {
      var extras = this.options.requestHeaders;

      if (Object.isFunction(extras.push))
        for (var i = 0, length = extras.length; i < length; i += 2)
          headers[extras[i]] = extras[i+1];
      else
        $H(extras).each(function(pair) { headers[pair.key] = pair.value });
    }

    for (var name in headers)
      this.transport.setRequestHeader(name, headers[name]);
  },

  success: function() {
    var status = this.getStatus();
    return !status || (status >= 200 && status < 300) || status == 304;
  },

  getStatus: function() {
    try {
      if (this.transport.status === 1223) return 204;
      return this.transport.status || 0;
    } catch (e) { return 0 }
  },

  respondToReadyState: function(readyState) {
    var state = Ajax.Request.Events[readyState], response = new Ajax.Response(this);

    if (state == 'Complete') {
      try {
        this._complete = true;
        (this.options['on' + response.status]
         || this.options['on' + (this.success() ? 'Success' : 'Failure')]
         || Prototype.emptyFunction)(response, response.headerJSON);
      } catch (e) {
        this.dispatchException(e);
      }

      var contentType = response.getHeader('Content-type');
      if (this.options.evalJS == 'force'
          || (this.options.evalJS && this.isSameOrigin() && contentType
          && contentType.match(/^\s*(text|application)\/(x-)?(java|ecma)script(;.*)?\s*$/i)))
        this.evalResponse();
    }

    try {
      (this.options['on' + state] || Prototype.emptyFunction)(response, response.headerJSON);
      Ajax.Responders.dispatch('on' + state, this, response, response.headerJSON);
    } catch (e) {
      this.dispatchException(e);
    }

    if (state == 'Complete') {
      this.transport.onreadystatechange = Prototype.emptyFunction;
    }
  },

  isSameOrigin: function() {
    var m = this.url.match(/^\s*https?:\/\/[^\/]*/);
    return !m || (m[0] == '#{protocol}//#{domain}#{port}'.interpolate({
      protocol: location.protocol,
      domain: document.domain,
      port: location.port ? ':' + location.port : ''
    }));
  },

  getHeader: function(name) {
    try {
      return this.transport.getResponseHeader(name) || null;
    } catch (e) { return null; }
  },

  evalResponse: function() {
    try {
      return eval((this.transport.responseText || '').unfilterJSON());
    } catch (e) {
      this.dispatchException(e);
    }
  },

  dispatchException: function(exception) {
    (this.options.onException || Prototype.emptyFunction)(this, exception);
    Ajax.Responders.dispatch('onException', this, exception);
  }
});

Ajax.Request.Events =
  ['Uninitialized', 'Loading', 'Loaded', 'Interactive', 'Complete'];








Ajax.Response = Class.create({
  initialize: function(request){
    this.request = request;
    var transport  = this.transport  = request.transport,
        readyState = this.readyState = transport.readyState;

    if ((readyState > 2 && !Prototype.Browser.IE) || readyState == 4) {
      this.status       = this.getStatus();
      this.statusText   = this.getStatusText();
      this.responseText = String.interpret(transport.responseText);
      this.headerJSON   = this._getHeaderJSON();
    }

    if (readyState == 4) {
      var xml = transport.responseXML;
      this.responseXML  = Object.isUndefined(xml) ? null : xml;
      this.responseJSON = this._getResponseJSON();
    }
  },

  status:      0,

  statusText: '',

  getStatus: Ajax.Request.prototype.getStatus,

  getStatusText: function() {
    try {
      return this.transport.statusText || '';
    } catch (e) { return '' }
  },

  getHeader: Ajax.Request.prototype.getHeader,

  getAllHeaders: function() {
    try {
      return this.getAllResponseHeaders();
    } catch (e) { return null }
  },

  getResponseHeader: function(name) {
    return this.transport.getResponseHeader(name);
  },

  getAllResponseHeaders: function() {
    return this.transport.getAllResponseHeaders();
  },

  _getHeaderJSON: function() {
    var json = this.getHeader('X-JSON');
    if (!json) return null;
    json = decodeURIComponent(escape(json));
    try {
      return json.evalJSON(this.request.options.sanitizeJSON ||
        !this.request.isSameOrigin());
    } catch (e) {
      this.request.dispatchException(e);
    }
  },

  _getResponseJSON: function() {
    var options = this.request.options;
    if (!options.evalJSON || (options.evalJSON != 'force' &&
      !(this.getHeader('Content-type') || '').include('application/json')) ||
        this.responseText.blank())
          return null;
    try {
      return this.responseText.evalJSON(options.sanitizeJSON ||
        !this.request.isSameOrigin());
    } catch (e) {
      this.request.dispatchException(e);
    }
  }
});

Ajax.Updater = Class.create(Ajax.Request, {
  initialize: function($super, container, url, options) {
    this.container = {
      success: (container.success || container),
      failure: (container.failure || (container.success ? null : container))
    };

    options = Object.clone(options);
    var onComplete = options.onComplete;
    options.onComplete = (function(response, json) {
      this.updateContent(response.responseText);
      if (Object.isFunction(onComplete)) onComplete(response, json);
    }).bind(this);

    $super(url, options);
  },

  updateContent: function(responseText) {
    var receiver = this.container[this.success() ? 'success' : 'failure'],
        options = this.options;

    if (!options.evalScripts) responseText = responseText.stripScripts();

    if (receiver = $(receiver)) {
      if (options.insertion) {
        if (Object.isString(options.insertion)) {
          var insertion = { }; insertion[options.insertion] = responseText;
          receiver.insert(insertion);
        }
        else options.insertion(receiver, responseText);
      }
      else receiver.update(responseText);
    }
  }
});

Ajax.PeriodicalUpdater = Class.create(Ajax.Base, {
  initialize: function($super, container, url, options) {
    $super(options);
    this.onComplete = this.options.onComplete;

    this.frequency = (this.options.frequency || 2);
    this.decay = (this.options.decay || 1);

    this.updater = { };
    this.container = container;
    this.url = url;

    this.start();
  },

  start: function() {
    this.options.onComplete = this.updateComplete.bind(this);
    this.onTimerEvent();
  },

  stop: function() {
    this.updater.options.onComplete = undefined;
    clearTimeout(this.timer);
    (this.onComplete || Prototype.emptyFunction).apply(this, arguments);
  },

  updateComplete: function(response) {
    if (this.options.decay) {
      this.decay = (response.responseText == this.lastText ?
        this.decay * this.options.decay : 1);

      this.lastText = response.responseText;
    }
    this.timer = this.onTimerEvent.bind(this).delay(this.decay * this.frequency);
  },

  onTimerEvent: function() {
    this.updater = new Ajax.Updater(this.container, this.url, this.options);
  }
});


function $(element) {
  if (arguments.length > 1) {
    for (var i = 0, elements = [], length = arguments.length; i < length; i++)
      elements.push($(arguments[i]));
    return elements;
  }
  if (Object.isString(element))
    element = document.getElementById(element);
  return Element.extend(element);
}

if (Prototype.BrowserFeatures.XPath) {
  document._getElementsByXPath = function(expression, parentElement) {
    var results = [];
    var query = document.evaluate(expression, $(parentElement) || document,
      null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
    for (var i = 0, length = query.snapshotLength; i < length; i++)
      results.push(Element.extend(query.snapshotItem(i)));
    return results;
  };
}

/*--------------------------------------------------------------------------*/

if (!Node) var Node = { };

if (!Node.ELEMENT_NODE) {
  Object.extend(Node, {
    ELEMENT_NODE: 1,
    ATTRIBUTE_NODE: 2,
    TEXT_NODE: 3,
    CDATA_SECTION_NODE: 4,
    ENTITY_REFERENCE_NODE: 5,
    ENTITY_NODE: 6,
    PROCESSING_INSTRUCTION_NODE: 7,
    COMMENT_NODE: 8,
    DOCUMENT_NODE: 9,
    DOCUMENT_TYPE_NODE: 10,
    DOCUMENT_FRAGMENT_NODE: 11,
    NOTATION_NODE: 12
  });
}



(function(global) {
  function shouldUseCache(tagName, attributes) {
    if (tagName === 'select') return false;
    if ('type' in attributes) return false;
    return true;
  }

  var HAS_EXTENDED_CREATE_ELEMENT_SYNTAX = (function(){
    try {
      var el = document.createElement('<input name="x">');
      return el.tagName.toLowerCase() === 'input' && el.name === 'x';
    }
    catch(err) {
      return false;
    }
  })();

  var element = global.Element;

  global.Element = function(tagName, attributes) {
    attributes = attributes || { };
    tagName = tagName.toLowerCase();
    var cache = Element.cache;

    if (HAS_EXTENDED_CREATE_ELEMENT_SYNTAX && attributes.name) {
      tagName = '<' + tagName + ' name="' + attributes.name + '">';
      delete attributes.name;
      return Element.writeAttribute(document.createElement(tagName), attributes);
    }

    if (!cache[tagName]) cache[tagName] = Element.extend(document.createElement(tagName));

    var node = shouldUseCache(tagName, attributes) ?
     cache[tagName].cloneNode(false) : document.createElement(tagName);

    return Element.writeAttribute(node, attributes);
  };

  Object.extend(global.Element, element || { });
  if (element) global.Element.prototype = element.prototype;

})(this);

Element.idCounter = 1;
Element.cache = { };

Element._purgeElement = function(element) {
  var uid = element._prototypeUID;
  if (uid) {
    Element.stopObserving(element);
    element._prototypeUID = void 0;
    delete Element.Storage[uid];
  }
}

Element.Methods = {
  visible: function(element) {
    return $(element).style.display != 'none';
  },

  toggle: function(element) {
    element = $(element);
    Element[Element.visible(element) ? 'hide' : 'show'](element);
    return element;
  },

  hide: function(element) {
    element = $(element);
    element.style.display = 'none';
    return element;
  },

  show: function(element) {
    element = $(element);
    element.style.display = '';
    return element;
  },

  remove: function(element) {
    element = $(element);
    element.parentNode.removeChild(element);
    return element;
  },

  update: (function(){

    var SELECT_ELEMENT_INNERHTML_BUGGY = (function(){
      var el = document.createElement("select"),
          isBuggy = true;
      el.innerHTML = "<option value=\"test\">test</option>";
      if (el.options && el.options[0]) {
        isBuggy = el.options[0].nodeName.toUpperCase() !== "OPTION";
      }
      el = null;
      return isBuggy;
    })();

    var TABLE_ELEMENT_INNERHTML_BUGGY = (function(){
      try {
        var el = document.createElement("table");
        if (el && el.tBodies) {
          el.innerHTML = "<tbody><tr><td>test</td></tr></tbody>";
          var isBuggy = typeof el.tBodies[0] == "undefined";
          el = null;
          return isBuggy;
        }
      } catch (e) {
        return true;
      }
    })();

    var LINK_ELEMENT_INNERHTML_BUGGY = (function() {
      try {
        var el = document.createElement('div');
        el.innerHTML = "<link>";
        var isBuggy = (el.childNodes.length === 0);
        el = null;
        return isBuggy;
      } catch(e) {
        return true;
      }
    })();

    var ANY_INNERHTML_BUGGY = SELECT_ELEMENT_INNERHTML_BUGGY ||
     TABLE_ELEMENT_INNERHTML_BUGGY || LINK_ELEMENT_INNERHTML_BUGGY;

    var SCRIPT_ELEMENT_REJECTS_TEXTNODE_APPENDING = (function () {
      var s = document.createElement("script"),
          isBuggy = false;
      try {
        s.appendChild(document.createTextNode(""));
        isBuggy = !s.firstChild ||
          s.firstChild && s.firstChild.nodeType !== 3;
      } catch (e) {
        isBuggy = true;
      }
      s = null;
      return isBuggy;
    })();


    function update(element, content) {
      element = $(element);
      var purgeElement = Element._purgeElement;

      var descendants = element.getElementsByTagName('*'),
       i = descendants.length;
      while (i--) purgeElement(descendants[i]);

      if (content && content.toElement)
        content = content.toElement();

      if (Object.isElement(content))
        return element.update().insert(content);

      content = Object.toHTML(content);

      var tagName = element.tagName.toUpperCase();

      if (tagName === 'SCRIPT' && SCRIPT_ELEMENT_REJECTS_TEXTNODE_APPENDING) {
        element.text = content;
        return element;
      }

      if (ANY_INNERHTML_BUGGY) {
        if (tagName in Element._insertionTranslations.tags) {
          while (element.firstChild) {
            element.removeChild(element.firstChild);
          }
          Element._getContentFromAnonymousElement(tagName, content.stripScripts())
            .each(function(node) {
              element.appendChild(node)
            });
        } else if (LINK_ELEMENT_INNERHTML_BUGGY && Object.isString(content) && content.indexOf('<link') > -1) {
          while (element.firstChild) {
            element.removeChild(element.firstChild);
          }
          var nodes = Element._getContentFromAnonymousElement(tagName, content.stripScripts(), true);
          nodes.each(function(node) { element.appendChild(node) });
        }
        else {
          element.innerHTML = content.stripScripts();
        }
      }
      else {
        element.innerHTML = content.stripScripts();
      }

      content.evalScripts.bind(content).defer();
      return element;
    }

    return update;
  })(),

  replace: function(element, content) {
    element = $(element);
    if (content && content.toElement) content = content.toElement();
    else if (!Object.isElement(content)) {
      content = Object.toHTML(content);
      var range = element.ownerDocument.createRange();
      range.selectNode(element);
      content.evalScripts.bind(content).defer();
      content = range.createContextualFragment(content.stripScripts());
    }
    element.parentNode.replaceChild(content, element);
    return element;
  },

  insert: function(element, insertions) {
    element = $(element);

    if (Object.isString(insertions) || Object.isNumber(insertions) ||
        Object.isElement(insertions) || (insertions && (insertions.toElement || insertions.toHTML)))
          insertions = {bottom:insertions};

    var content, insert, tagName, childNodes;

    for (var position in insertions) {
      content  = insertions[position];
      position = position.toLowerCase();
      insert = Element._insertionTranslations[position];

      if (content && content.toElement) content = content.toElement();
      if (Object.isElement(content)) {
        insert(element, content);
        continue;
      }

      content = Object.toHTML(content);

      tagName = ((position == 'before' || position == 'after')
        ? element.parentNode : element).tagName.toUpperCase();

      childNodes = Element._getContentFromAnonymousElement(tagName, content.stripScripts());

      if (position == 'top' || position == 'after') childNodes.reverse();
      childNodes.each(insert.curry(element));

      content.evalScripts.bind(content).defer();
    }

    return element;
  },

  wrap: function(element, wrapper, attributes) {
    element = $(element);
    if (Object.isElement(wrapper))
      $(wrapper).writeAttribute(attributes || { });
    else if (Object.isString(wrapper)) wrapper = new Element(wrapper, attributes);
    else wrapper = new Element('div', wrapper);
    if (element.parentNode)
      element.parentNode.replaceChild(wrapper, element);
    wrapper.appendChild(element);
    return wrapper;
  },

  inspect: function(element) {
    element = $(element);
    var result = '<' + element.tagName.toLowerCase();
    $H({'id': 'id', 'className': 'class'}).each(function(pair) {
      var property = pair.first(),
          attribute = pair.last(),
          value = (element[property] || '').toString();
      if (value) result += ' ' + attribute + '=' + value.inspect(true);
    });
    return result + '>';
  },

  recursivelyCollect: function(element, property, maximumLength) {
    element = $(element);
    maximumLength = maximumLength || -1;
    var elements = [];

    while (element = element[property]) {
      if (element.nodeType == 1)
        elements.push(Element.extend(element));
      if (elements.length == maximumLength)
        break;
    }

    return elements;
  },

  ancestors: function(element) {
    return Element.recursivelyCollect(element, 'parentNode');
  },

  descendants: function(element) {
    return Element.select(element, "*");
  },

  firstDescendant: function(element) {
    element = $(element).firstChild;
    while (element && element.nodeType != 1) element = element.nextSibling;
    return $(element);
  },

  immediateDescendants: function(element) {
    var results = [], child = $(element).firstChild;
    while (child) {
      if (child.nodeType === 1) {
        results.push(Element.extend(child));
      }
      child = child.nextSibling;
    }
    return results;
  },

  previousSiblings: function(element, maximumLength) {
    return Element.recursivelyCollect(element, 'previousSibling');
  },

  nextSiblings: function(element) {
    return Element.recursivelyCollect(element, 'nextSibling');
  },

  siblings: function(element) {
    element = $(element);
    return Element.previousSiblings(element).reverse()
      .concat(Element.nextSiblings(element));
  },

  match: function(element, selector) {
    element = $(element);
    if (Object.isString(selector))
      return Prototype.Selector.match(element, selector);
    return selector.match(element);
  },

  up: function(element, expression, index) {
    element = $(element);
    if (arguments.length == 1) return $(element.parentNode);
    var ancestors = Element.ancestors(element);
    return Object.isNumber(expression) ? ancestors[expression] :
      Prototype.Selector.find(ancestors, expression, index);
  },

  down: function(element, expression, index) {
    element = $(element);
    if (arguments.length == 1) return Element.firstDescendant(element);
    return Object.isNumber(expression) ? Element.descendants(element)[expression] :
      Element.select(element, expression)[index || 0];
  },

  previous: function(element, expression, index) {
    element = $(element);
    if (Object.isNumber(expression)) index = expression, expression = false;
    if (!Object.isNumber(index)) index = 0;

    if (expression) {
      return Prototype.Selector.find(element.previousSiblings(), expression, index);
    } else {
      return element.recursivelyCollect("previousSibling", index + 1)[index];
    }
  },

  next: function(element, expression, index) {
    element = $(element);
    if (Object.isNumber(expression)) index = expression, expression = false;
    if (!Object.isNumber(index)) index = 0;

    if (expression) {
      return Prototype.Selector.find(element.nextSiblings(), expression, index);
    } else {
      var maximumLength = Object.isNumber(index) ? index + 1 : 1;
      return element.recursivelyCollect("nextSibling", index + 1)[index];
    }
  },


  select: function(element) {
    element = $(element);
    var expressions = Array.prototype.slice.call(arguments, 1).join(', ');
    return Prototype.Selector.select(expressions, element);
  },

  adjacent: function(element) {
    element = $(element);
    var expressions = Array.prototype.slice.call(arguments, 1).join(', ');
    return Prototype.Selector.select(expressions, element.parentNode).without(element);
  },

  identify: function(element) {
    element = $(element);
    var id = Element.readAttribute(element, 'id');
    if (id) return id;
    do { id = 'anonymous_element_' + Element.idCounter++ } while ($(id));
    Element.writeAttribute(element, 'id', id);
    return id;
  },

  readAttribute: function(element, name) {
    element = $(element);
    if (Prototype.Browser.IE) {
      var t = Element._attributeTranslations.read;
      if (t.values[name]) return t.values[name](element, name);
      if (t.names[name]) name = t.names[name];
      if (name.include(':')) {
        return (!element.attributes || !element.attributes[name]) ? null :
         element.attributes[name].value;
      }
    }
    return element.getAttribute(name);
  },

  writeAttribute: function(element, name, value) {
    element = $(element);
    var attributes = { }, t = Element._attributeTranslations.write;

    if (typeof name == 'object') attributes = name;
    else attributes[name] = Object.isUndefined(value) ? true : value;

    for (var attr in attributes) {
      name = t.names[attr] || attr;
      value = attributes[attr];
      if (t.values[attr]) name = t.values[attr](element, value);
      if (value === false || value === null)
        element.removeAttribute(name);
      else if (value === true)
        element.setAttribute(name, name);
      else element.setAttribute(name, value);
    }
    return element;
  },

  getHeight: function(element) {
    return Element.getDimensions(element).height;
  },

  getWidth: function(element) {
    return Element.getDimensions(element).width;
  },

  classNames: function(element) {
    return new Element.ClassNames(element);
  },

  hasClassName: function(element, className) {
    if (!(element = $(element))) return;
    var elementClassName = element.className;
    return (elementClassName.length > 0 && (elementClassName == className ||
      new RegExp("(^|\\s)" + className + "(\\s|$)").test(elementClassName)));
  },

  addClassName: function(element, className) {
    if (!(element = $(element))) return;
    if (!Element.hasClassName(element, className))
      element.className += (element.className ? ' ' : '') + className;
    return element;
  },

  removeClassName: function(element, className) {
    if (!(element = $(element))) return;
    element.className = element.className.replace(
      new RegExp("(^|\\s+)" + className + "(\\s+|$)"), ' ').strip();
    return element;
  },

  toggleClassName: function(element, className) {
    if (!(element = $(element))) return;
    return Element[Element.hasClassName(element, className) ?
      'removeClassName' : 'addClassName'](element, className);
  },

  cleanWhitespace: function(element) {
    element = $(element);
    var node = element.firstChild;
    while (node) {
      var nextNode = node.nextSibling;
      if (node.nodeType == 3 && !/\S/.test(node.nodeValue))
        element.removeChild(node);
      node = nextNode;
    }
    return element;
  },

  empty: function(element) {
    return $(element).innerHTML.blank();
  },

  descendantOf: function(element, ancestor) {
    element = $(element), ancestor = $(ancestor);

    if (element.compareDocumentPosition)
      return (element.compareDocumentPosition(ancestor) & 8) === 8;

    if (ancestor.contains)
      return ancestor.contains(element) && ancestor !== element;

    while (element = element.parentNode)
      if (element == ancestor) return true;

    return false;
  },

  scrollTo: function(element) {
    element = $(element);
    var pos = Element.cumulativeOffset(element);
    window.scrollTo(pos[0], pos[1]);
    return element;
  },

  getStyle: function(element, style) {
    element = $(element);
    style = style == 'float' ? 'cssFloat' : style.camelize();
    var value = element.style[style];
    if (!value || value == 'auto') {
      var css = document.defaultView.getComputedStyle(element, null);
      value = css ? css[style] : null;
    }
    if (style == 'opacity') return value ? parseFloat(value) : 1.0;
    return value == 'auto' ? null : value;
  },

  getOpacity: function(element) {
    return $(element).getStyle('opacity');
  },

  setStyle: function(element, styles) {
    element = $(element);
    var elementStyle = element.style, match;
    if (Object.isString(styles)) {
      element.style.cssText += ';' + styles;
      return styles.include('opacity') ?
        element.setOpacity(styles.match(/opacity:\s*(\d?\.?\d*)/)[1]) : element;
    }
    for (var property in styles)
      if (property == 'opacity') element.setOpacity(styles[property]);
      else
        elementStyle[(property == 'float' || property == 'cssFloat') ?
          (Object.isUndefined(elementStyle.styleFloat) ? 'cssFloat' : 'styleFloat') :
            property] = styles[property];

    return element;
  },

  setOpacity: function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1 || value === '') ? '' :
      (value < 0.00001) ? 0 : value;
    return element;
  },

  makePositioned: function(element) {
    element = $(element);
    var pos = Element.getStyle(element, 'position');
    if (pos == 'static' || !pos) {
      element._madePositioned = true;
      element.style.position = 'relative';
      if (Prototype.Browser.Opera) {
        element.style.top = 0;
        element.style.left = 0;
      }
    }
    return element;
  },

  undoPositioned: function(element) {
    element = $(element);
    if (element._madePositioned) {
      element._madePositioned = undefined;
      element.style.position =
        element.style.top =
        element.style.left =
        element.style.bottom =
        element.style.right = '';
    }
    return element;
  },

  makeClipping: function(element) {
    element = $(element);
    if (element._overflow) return element;
    element._overflow = Element.getStyle(element, 'overflow') || 'auto';
    if (element._overflow !== 'hidden')
      element.style.overflow = 'hidden';
    return element;
  },

  undoClipping: function(element) {
    element = $(element);
    if (!element._overflow) return element;
    element.style.overflow = element._overflow == 'auto' ? '' : element._overflow;
    element._overflow = null;
    return element;
  },

  clonePosition: function(element, source) {
    var options = Object.extend({
      setLeft:    true,
      setTop:     true,
      setWidth:   true,
      setHeight:  true,
      offsetTop:  0,
      offsetLeft: 0
    }, arguments[2] || { });

    source = $(source);
    var p = Element.viewportOffset(source), delta = [0, 0], parent = null;

    element = $(element);

    if (Element.getStyle(element, 'position') == 'absolute') {
      parent = Element.getOffsetParent(element);
      delta = Element.viewportOffset(parent);
    }

    if (parent == document.body) {
      delta[0] -= document.body.offsetLeft;
      delta[1] -= document.body.offsetTop;
    }

    if (options.setLeft)   element.style.left  = (p[0] - delta[0] + options.offsetLeft) + 'px';
    if (options.setTop)    element.style.top   = (p[1] - delta[1] + options.offsetTop) + 'px';
    if (options.setWidth)  element.style.width = source.offsetWidth + 'px';
    if (options.setHeight) element.style.height = source.offsetHeight + 'px';
    return element;
  }
};

Object.extend(Element.Methods, {
  getElementsBySelector: Element.Methods.select,

  childElements: Element.Methods.immediateDescendants
});

Element._attributeTranslations = {
  write: {
    names: {
      className: 'class',
      htmlFor:   'for'
    },
    values: { }
  }
};

if (Prototype.Browser.Opera) {
  Element.Methods.getStyle = Element.Methods.getStyle.wrap(
    function(proceed, element, style) {
      switch (style) {
        case 'height': case 'width':
          if (!Element.visible(element)) return null;

          var dim = parseInt(proceed(element, style), 10);

          if (dim !== element['offset' + style.capitalize()])
            return dim + 'px';

          var properties;
          if (style === 'height') {
            properties = ['border-top-width', 'padding-top',
             'padding-bottom', 'border-bottom-width'];
          }
          else {
            properties = ['border-left-width', 'padding-left',
             'padding-right', 'border-right-width'];
          }
          return properties.inject(dim, function(memo, property) {
            var val = proceed(element, property);
            return val === null ? memo : memo - parseInt(val, 10);
          }) + 'px';
        default: return proceed(element, style);
      }
    }
  );

  Element.Methods.readAttribute = Element.Methods.readAttribute.wrap(
    function(proceed, element, attribute) {
      if (attribute === 'title') return element.title;
      return proceed(element, attribute);
    }
  );
}

else if (Prototype.Browser.IE) {
  Element.Methods.getStyle = function(element, style) {
    element = $(element);
    style = (style == 'float' || style == 'cssFloat') ? 'styleFloat' : style.camelize();
    var value = element.style[style];
    if (!value && element.currentStyle) value = element.currentStyle[style];

    if (style == 'opacity') {
      if (value = (element.getStyle('filter') || '').match(/alpha\(opacity=(.*)\)/))
        if (value[1]) return parseFloat(value[1]) / 100;
      return 1.0;
    }

    if (value == 'auto') {
      if ((style == 'width' || style == 'height') && (element.getStyle('display') != 'none'))
        return element['offset' + style.capitalize()] + 'px';
      return null;
    }
    return value;
  };

  Element.Methods.setOpacity = function(element, value) {
    function stripAlpha(filter){
      return filter.replace(/alpha\([^\)]*\)/gi,'');
    }
    element = $(element);
    var currentStyle = element.currentStyle;
    if ((currentStyle && !currentStyle.hasLayout) ||
      (!currentStyle && element.style.zoom == 'normal'))
        element.style.zoom = 1;

    var filter = element.getStyle('filter'), style = element.style;
    if (value == 1 || value === '') {
      (filter = stripAlpha(filter)) ?
        style.filter = filter : style.removeAttribute('filter');
      return element;
    } else if (value < 0.00001) value = 0;
    style.filter = stripAlpha(filter) +
      'alpha(opacity=' + (value * 100) + ')';
    return element;
  };

  Element._attributeTranslations = (function(){

    var classProp = 'className',
        forProp = 'for',
        el = document.createElement('div');

    el.setAttribute(classProp, 'x');

    if (el.className !== 'x') {
      el.setAttribute('class', 'x');
      if (el.className === 'x') {
        classProp = 'class';
      }
    }
    el = null;

    el = document.createElement('label');
    el.setAttribute(forProp, 'x');
    if (el.htmlFor !== 'x') {
      el.setAttribute('htmlFor', 'x');
      if (el.htmlFor === 'x') {
        forProp = 'htmlFor';
      }
    }
    el = null;

    return {
      read: {
        names: {
          'class':      classProp,
          'className':  classProp,
          'for':        forProp,
          'htmlFor':    forProp
        },
        values: {
          _getAttr: function(element, attribute) {
            return element.getAttribute(attribute);
          },
          _getAttr2: function(element, attribute) {
            return element.getAttribute(attribute, 2);
          },
          _getAttrNode: function(element, attribute) {
            var node = element.getAttributeNode(attribute);
            return node ? node.value : "";
          },
          _getEv: (function(){

            var el = document.createElement('div'), f;
            el.onclick = Prototype.emptyFunction;
            var value = el.getAttribute('onclick');

            if (String(value).indexOf('{') > -1) {
              f = function(element, attribute) {
                attribute = element.getAttribute(attribute);
                if (!attribute) return null;
                attribute = attribute.toString();
                attribute = attribute.split('{')[1];
                attribute = attribute.split('}')[0];
                return attribute.strip();
              };
            }
            else if (value === '') {
              f = function(element, attribute) {
                attribute = element.getAttribute(attribute);
                if (!attribute) return null;
                return attribute.strip();
              };
            }
            el = null;
            return f;
          })(),
          _flag: function(element, attribute) {
            return $(element).hasAttribute(attribute) ? attribute : null;
          },
          style: function(element) {
            return element.style.cssText.toLowerCase();
          },
          title: function(element) {
            return element.title;
          }
        }
      }
    }
  })();

  Element._attributeTranslations.write = {
    names: Object.extend({
      cellpadding: 'cellPadding',
      cellspacing: 'cellSpacing'
    }, Element._attributeTranslations.read.names),
    values: {
      checked: function(element, value) {
        element.checked = !!value;
      },

      style: function(element, value) {
        element.style.cssText = value ? value : '';
      }
    }
  };

  Element._attributeTranslations.has = {};

  $w('colSpan rowSpan vAlign dateTime accessKey tabIndex ' +
      'encType maxLength readOnly longDesc frameBorder').each(function(attr) {
    Element._attributeTranslations.write.names[attr.toLowerCase()] = attr;
    Element._attributeTranslations.has[attr.toLowerCase()] = attr;
  });

  (function(v) {
    Object.extend(v, {
      href:        v._getAttr2,
      src:         v._getAttr2,
      type:        v._getAttr,
      action:      v._getAttrNode,
      disabled:    v._flag,
      checked:     v._flag,
      readonly:    v._flag,
      multiple:    v._flag,
      onload:      v._getEv,
      onunload:    v._getEv,
      onclick:     v._getEv,
      ondblclick:  v._getEv,
      onmousedown: v._getEv,
      onmouseup:   v._getEv,
      onmouseover: v._getEv,
      onmousemove: v._getEv,
      onmouseout:  v._getEv,
      onfocus:     v._getEv,
      onblur:      v._getEv,
      onkeypress:  v._getEv,
      onkeydown:   v._getEv,
      onkeyup:     v._getEv,
      onsubmit:    v._getEv,
      onreset:     v._getEv,
      onselect:    v._getEv,
      onchange:    v._getEv
    });
  })(Element._attributeTranslations.read.values);

  if (Prototype.BrowserFeatures.ElementExtensions) {
    (function() {
      function _descendants(element) {
        var nodes = element.getElementsByTagName('*'), results = [];
        for (var i = 0, node; node = nodes[i]; i++)
          if (node.tagName !== "!") // Filter out comment nodes.
            results.push(node);
        return results;
      }

      Element.Methods.down = function(element, expression, index) {
        element = $(element);
        if (arguments.length == 1) return element.firstDescendant();
        return Object.isNumber(expression) ? _descendants(element)[expression] :
          Element.select(element, expression)[index || 0];
      }
    })();
  }

}

else if (Prototype.Browser.Gecko && /rv:1\.8\.0/.test(navigator.userAgent)) {
  Element.Methods.setOpacity = function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1) ? 0.999999 :
      (value === '') ? '' : (value < 0.00001) ? 0 : value;
    return element;
  };
}

else if (Prototype.Browser.WebKit) {
  Element.Methods.setOpacity = function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1 || value === '') ? '' :
      (value < 0.00001) ? 0 : value;

    if (value == 1)
      if (element.tagName.toUpperCase() == 'IMG' && element.width) {
        element.width++; element.width--;
      } else try {
        var n = document.createTextNode(' ');
        element.appendChild(n);
        element.removeChild(n);
      } catch (e) { }

    return element;
  };
}

if ('outerHTML' in document.documentElement) {
  Element.Methods.replace = function(element, content) {
    element = $(element);

    if (content && content.toElement) content = content.toElement();
    if (Object.isElement(content)) {
      element.parentNode.replaceChild(content, element);
      return element;
    }

    content = Object.toHTML(content);
    var parent = element.parentNode, tagName = parent.tagName.toUpperCase();

    if (Element._insertionTranslations.tags[tagName]) {
      var nextSibling = element.next(),
          fragments = Element._getContentFromAnonymousElement(tagName, content.stripScripts());
      parent.removeChild(element);
      if (nextSibling)
        fragments.each(function(node) { parent.insertBefore(node, nextSibling) });
      else
        fragments.each(function(node) { parent.appendChild(node) });
    }
    else element.outerHTML = content.stripScripts();

    content.evalScripts.bind(content).defer();
    return element;
  };
}

Element._returnOffset = function(l, t) {
  var result = [l, t];
  result.left = l;
  result.top = t;
  return result;
};

Element._getContentFromAnonymousElement = function(tagName, html, force) {
  var div = new Element('div'),
      t = Element._insertionTranslations.tags[tagName];

  var workaround = false;
  if (t) workaround = true;
  else if (force) {
    workaround = true;
    t = ['', '', 0];
  }

  if (workaround) {
    div.innerHTML = '&nbsp;' + t[0] + html + t[1];
    div.removeChild(div.firstChild);
    for (var i = t[2]; i--; ) {
      div = div.firstChild;
    }
  }
  else {
    div.innerHTML = html;
  }
  return $A(div.childNodes);
};

Element._insertionTranslations = {
  before: function(element, node) {
    element.parentNode.insertBefore(node, element);
  },
  top: function(element, node) {
    element.insertBefore(node, element.firstChild);
  },
  bottom: function(element, node) {
    element.appendChild(node);
  },
  after: function(element, node) {
    element.parentNode.insertBefore(node, element.nextSibling);
  },
  tags: {
    TABLE:  ['<table>',                '</table>',                   1],
    TBODY:  ['<table><tbody>',         '</tbody></table>',           2],
    TR:     ['<table><tbody><tr>',     '</tr></tbody></table>',      3],
    TD:     ['<table><tbody><tr><td>', '</td></tr></tbody></table>', 4],
    SELECT: ['<select>',               '</select>',                  1]
  }
};

(function() {
  var tags = Element._insertionTranslations.tags;
  Object.extend(tags, {
    THEAD: tags.TBODY,
    TFOOT: tags.TBODY,
    TH:    tags.TD
  });
})();

Element.Methods.Simulated = {
  hasAttribute: function(element, attribute) {
    attribute = Element._attributeTranslations.has[attribute] || attribute;
    var node = $(element).getAttributeNode(attribute);
    return !!(node && node.specified);
  }
};

Element.Methods.ByTag = { };

Object.extend(Element, Element.Methods);

(function(div) {

  if (!Prototype.BrowserFeatures.ElementExtensions && div['__proto__']) {
    window.HTMLElement = { };
    window.HTMLElement.prototype = div['__proto__'];
    Prototype.BrowserFeatures.ElementExtensions = true;
  }

  div = null;

})(document.createElement('div'));

Element.extend = (function() {

  function checkDeficiency(tagName) {
    if (typeof window.Element != 'undefined') {
      var proto = window.Element.prototype;
      if (proto) {
        var id = '_' + (Math.random()+'').slice(2),
            el = document.createElement(tagName);
        proto[id] = 'x';
        var isBuggy = (el[id] !== 'x');
        delete proto[id];
        el = null;
        return isBuggy;
      }
    }
    return false;
  }

  function extendElementWith(element, methods) {
    for (var property in methods) {
      var value = methods[property];
      if (Object.isFunction(value) && !(property in element))
        element[property] = value.methodize();
    }
  }

  var HTMLOBJECTELEMENT_PROTOTYPE_BUGGY = checkDeficiency('object');

  if (Prototype.BrowserFeatures.SpecificElementExtensions) {
    if (HTMLOBJECTELEMENT_PROTOTYPE_BUGGY) {
      return function(element) {
        if (element && typeof element._extendedByPrototype == 'undefined') {
          var t = element.tagName;
          if (t && (/^(?:object|applet|embed)$/i.test(t))) {
            extendElementWith(element, Element.Methods);
            extendElementWith(element, Element.Methods.Simulated);
            extendElementWith(element, Element.Methods.ByTag[t.toUpperCase()]);
          }
        }
        return element;
      }
    }
    return Prototype.K;
  }

  var Methods = { }, ByTag = Element.Methods.ByTag;

  var extend = Object.extend(function(element) {
    if (!element || typeof element._extendedByPrototype != 'undefined' ||
        element.nodeType != 1 || element == window) return element;

    var methods = Object.clone(Methods),
        tagName = element.tagName.toUpperCase();

    if (ByTag[tagName]) Object.extend(methods, ByTag[tagName]);

    extendElementWith(element, methods);

    element._extendedByPrototype = Prototype.emptyFunction;
    return element;

  }, {
    refresh: function() {
      if (!Prototype.BrowserFeatures.ElementExtensions) {
        Object.extend(Methods, Element.Methods);
        Object.extend(Methods, Element.Methods.Simulated);
      }
    }
  });

  extend.refresh();
  return extend;
})();

if (document.documentElement.hasAttribute) {
  Element.hasAttribute = function(element, attribute) {
    return element.hasAttribute(attribute);
  };
}
else {
  Element.hasAttribute = Element.Methods.Simulated.hasAttribute;
}

Element.addMethods = function(methods) {
  var F = Prototype.BrowserFeatures, T = Element.Methods.ByTag;

  if (!methods) {
    Object.extend(Form, Form.Methods);
    Object.extend(Form.Element, Form.Element.Methods);
    Object.extend(Element.Methods.ByTag, {
      "FORM":     Object.clone(Form.Methods),
      "INPUT":    Object.clone(Form.Element.Methods),
      "SELECT":   Object.clone(Form.Element.Methods),
      "TEXTAREA": Object.clone(Form.Element.Methods),
      "BUTTON":   Object.clone(Form.Element.Methods)
    });
  }

  if (arguments.length == 2) {
    var tagName = methods;
    methods = arguments[1];
  }

  if (!tagName) Object.extend(Element.Methods, methods || { });
  else {
    if (Object.isArray(tagName)) tagName.each(extend);
    else extend(tagName);
  }

  function extend(tagName) {
    tagName = tagName.toUpperCase();
    if (!Element.Methods.ByTag[tagName])
      Element.Methods.ByTag[tagName] = { };
    Object.extend(Element.Methods.ByTag[tagName], methods);
  }

  function copy(methods, destination, onlyIfAbsent) {
    onlyIfAbsent = onlyIfAbsent || false;
    for (var property in methods) {
      var value = methods[property];
      if (!Object.isFunction(value)) continue;
      if (!onlyIfAbsent || !(property in destination))
        destination[property] = value.methodize();
    }
  }

  function findDOMClass(tagName) {
    var klass;
    var trans = {
      "OPTGROUP": "OptGroup", "TEXTAREA": "TextArea", "P": "Paragraph",
      "FIELDSET": "FieldSet", "UL": "UList", "OL": "OList", "DL": "DList",
      "DIR": "Directory", "H1": "Heading", "H2": "Heading", "H3": "Heading",
      "H4": "Heading", "H5": "Heading", "H6": "Heading", "Q": "Quote",
      "INS": "Mod", "DEL": "Mod", "A": "Anchor", "IMG": "Image", "CAPTION":
      "TableCaption", "COL": "TableCol", "COLGROUP": "TableCol", "THEAD":
      "TableSection", "TFOOT": "TableSection", "TBODY": "TableSection", "TR":
      "TableRow", "TH": "TableCell", "TD": "TableCell", "FRAMESET":
      "FrameSet", "IFRAME": "IFrame"
    };
    if (trans[tagName]) klass = 'HTML' + trans[tagName] + 'Element';
    if (window[klass]) return window[klass];
    klass = 'HTML' + tagName + 'Element';
    if (window[klass]) return window[klass];
    klass = 'HTML' + tagName.capitalize() + 'Element';
    if (window[klass]) return window[klass];

    var element = document.createElement(tagName),
        proto = element['__proto__'] || element.constructor.prototype;

    element = null;
    return proto;
  }

  var elementPrototype = window.HTMLElement ? HTMLElement.prototype :
   Element.prototype;

  if (F.ElementExtensions) {
    copy(Element.Methods, elementPrototype);
    copy(Element.Methods.Simulated, elementPrototype, true);
  }

  if (F.SpecificElementExtensions) {
    for (var tag in Element.Methods.ByTag) {
      var klass = findDOMClass(tag);
      if (Object.isUndefined(klass)) continue;
      copy(T[tag], klass.prototype);
    }
  }

  Object.extend(Element, Element.Methods);
  delete Element.ByTag;

  if (Element.extend.refresh) Element.extend.refresh();
  Element.cache = { };
};


document.viewport = {

  getDimensions: function() {
    return { width: this.getWidth(), height: this.getHeight() };
  },

  getScrollOffsets: function() {
    return Element._returnOffset(
      window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
      window.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop);
  }
};

(function(viewport) {
  var B = Prototype.Browser, doc = document, element, property = {};

  function getRootElement() {
    if (B.WebKit && !doc.evaluate)
      return document;

    if (B.Opera && window.parseFloat(window.opera.version()) < 9.5)
      return document.body;

    return document.documentElement;
  }

  function define(D) {
    if (!element) element = getRootElement();

    property[D] = 'client' + D;

    viewport['get' + D] = function() { return element[property[D]] };
    return viewport['get' + D]();
  }

  viewport.getWidth  = define.curry('Width');

  viewport.getHeight = define.curry('Height');
})(document.viewport);


Element.Storage = {
  UID: 1
};

Element.addMethods({
  getStorage: function(element) {
    if (!(element = $(element))) return;

    var uid;
    if (element === window) {
      uid = 0;
    } else {
      if (typeof element._prototypeUID === "undefined")
        element._prototypeUID = Element.Storage.UID++;
      uid = element._prototypeUID;
    }

    if (!Element.Storage[uid])
      Element.Storage[uid] = $H();

    return Element.Storage[uid];
  },

  store: function(element, key, value) {
    if (!(element = $(element))) return;

    if (arguments.length === 2) {
      Element.getStorage(element).update(key);
    } else {
      Element.getStorage(element).set(key, value);
    }

    return element;
  },

  retrieve: function(element, key, defaultValue) {
    if (!(element = $(element))) return;
    var hash = Element.getStorage(element), value = hash.get(key);

    if (Object.isUndefined(value)) {
      hash.set(key, defaultValue);
      value = defaultValue;
    }

    return value;
  },

  clone: function(element, deep) {
    if (!(element = $(element))) return;
    var clone = element.cloneNode(deep);
    clone._prototypeUID = void 0;
    if (deep) {
      var descendants = Element.select(clone, '*'),
          i = descendants.length;
      while (i--) {
        descendants[i]._prototypeUID = void 0;
      }
    }
    return Element.extend(clone);
  },

  purge: function(element) {
    if (!(element = $(element))) return;
    var purgeElement = Element._purgeElement;

    purgeElement(element);

    var descendants = element.getElementsByTagName('*'),
     i = descendants.length;

    while (i--) purgeElement(descendants[i]);

    return null;
  }
});

(function() {

  function toDecimal(pctString) {
    var match = pctString.match(/^(\d+)%?$/i);
    if (!match) return null;
    return (Number(match[1]) / 100);
  }

  function getPixelValue(value, property, context) {
    var element = null;
    if (Object.isElement(value)) {
      element = value;
      value = element.getStyle(property);
    }

    if (value === null) {
      return null;
    }

    if ((/^(?:-)?\d+(\.\d+)?(px)?$/i).test(value)) {
      return window.parseFloat(value);
    }

    var isPercentage = value.include('%'), isViewport = (context === document.viewport);

    if (/\d/.test(value) && element && element.runtimeStyle && !(isPercentage && isViewport)) {
      var style = element.style.left, rStyle = element.runtimeStyle.left;
      element.runtimeStyle.left = element.currentStyle.left;
      element.style.left = value || 0;
      value = element.style.pixelLeft;
      element.style.left = style;
      element.runtimeStyle.left = rStyle;

      return value;
    }

    if (element && isPercentage) {
      context = context || element.parentNode;
      var decimal = toDecimal(value);
      var whole = null;
      var position = element.getStyle('position');

      var isHorizontal = property.include('left') || property.include('right') ||
       property.include('width');

      var isVertical =  property.include('top') || property.include('bottom') ||
        property.include('height');

      if (context === document.viewport) {
        if (isHorizontal) {
          whole = document.viewport.getWidth();
        } else if (isVertical) {
          whole = document.viewport.getHeight();
        }
      } else {
        if (isHorizontal) {
          whole = $(context).measure('width');
        } else if (isVertical) {
          whole = $(context).measure('height');
        }
      }

      return (whole === null) ? 0 : whole * decimal;
    }

    return 0;
  }

  function toCSSPixels(number) {
    if (Object.isString(number) && number.endsWith('px')) {
      return number;
    }
    return number + 'px';
  }

  function isDisplayed(element) {
    var originalElement = element;
    while (element && element.parentNode) {
      var display = element.getStyle('display');
      if (display === 'none') {
        return false;
      }
      element = $(element.parentNode);
    }
    return true;
  }

  var hasLayout = Prototype.K;
  if ('currentStyle' in document.documentElement) {
    hasLayout = function(element) {
      if (!element.currentStyle.hasLayout) {
        element.style.zoom = 1;
      }
      return element;
    };
  }

  function cssNameFor(key) {
    if (key.include('border')) key = key + '-width';
    return key.camelize();
  }

  Element.Layout = Class.create(Hash, {
    initialize: function($super, element, preCompute) {
      $super();
      this.element = $(element);

      Element.Layout.PROPERTIES.each( function(property) {
        this._set(property, null);
      }, this);

      if (preCompute) {
        this._preComputing = true;
        this._begin();
        Element.Layout.PROPERTIES.each( this._compute, this );
        this._end();
        this._preComputing = false;
      }
    },

    _set: function(property, value) {
      return Hash.prototype.set.call(this, property, value);
    },

    set: function(property, value) {
      throw "Properties of Element.Layout are read-only.";
    },

    get: function($super, property) {
      var value = $super(property);
      return value === null ? this._compute(property) : value;
    },

    _begin: function() {
      if (this._prepared) return;

      var element = this.element;
      if (isDisplayed(element)) {
        this._prepared = true;
        return;
      }

      var originalStyles = {
        position:   element.style.position   || '',
        width:      element.style.width      || '',
        visibility: element.style.visibility || '',
        display:    element.style.display    || ''
      };

      element.store('prototype_original_styles', originalStyles);

      var position = element.getStyle('position'),
       width = element.getStyle('width');

      if (width === "0px" || width === null) {
        element.style.display = 'block';
        width = element.getStyle('width');
      }

      var context = (position === 'fixed') ? document.viewport :
       element.parentNode;

      element.setStyle({
        position:   'absolute',
        visibility: 'hidden',
        display:    'block'
      });

      var positionedWidth = element.getStyle('width');

      var newWidth;
      if (width && (positionedWidth === width)) {
        newWidth = getPixelValue(element, 'width', context);
      } else if (position === 'absolute' || position === 'fixed') {
        newWidth = getPixelValue(element, 'width', context);
      } else {
        var parent = element.parentNode, pLayout = $(parent).getLayout();

        newWidth = pLayout.get('width') -
         this.get('margin-left') -
         this.get('border-left') -
         this.get('padding-left') -
         this.get('padding-right') -
         this.get('border-right') -
         this.get('margin-right');
      }

      element.setStyle({ width: newWidth + 'px' });

      this._prepared = true;
    },

    _end: function() {
      var element = this.element;
      var originalStyles = element.retrieve('prototype_original_styles');
      element.store('prototype_original_styles', null);
      element.setStyle(originalStyles);
      this._prepared = false;
    },

    _compute: function(property) {
      var COMPUTATIONS = Element.Layout.COMPUTATIONS;
      if (!(property in COMPUTATIONS)) {
        throw "Property not found.";
      }

      return this._set(property, COMPUTATIONS[property].call(this, this.element));
    },

    toObject: function() {
      var args = $A(arguments);
      var keys = (args.length === 0) ? Element.Layout.PROPERTIES :
       args.join(' ').split(' ');
      var obj = {};
      keys.each( function(key) {
        if (!Element.Layout.PROPERTIES.include(key)) return;
        var value = this.get(key);
        if (value != null) obj[key] = value;
      }, this);
      return obj;
    },

    toHash: function() {
      var obj = this.toObject.apply(this, arguments);
      return new Hash(obj);
    },

    toCSS: function() {
      var args = $A(arguments);
      var keys = (args.length === 0) ? Element.Layout.PROPERTIES :
       args.join(' ').split(' ');
      var css = {};

      keys.each( function(key) {
        if (!Element.Layout.PROPERTIES.include(key)) return;
        if (Element.Layout.COMPOSITE_PROPERTIES.include(key)) return;

        var value = this.get(key);
        if (value != null) css[cssNameFor(key)] = value + 'px';
      }, this);
      return css;
    },

    inspect: function() {
      return "#<Element.Layout>";
    }
  });

  Object.extend(Element.Layout, {
    PROPERTIES: $w('height width top left right bottom border-left border-right border-top border-bottom padding-left padding-right padding-top padding-bottom margin-top margin-bottom margin-left margin-right padding-box-width padding-box-height border-box-width border-box-height margin-box-width margin-box-height'),

    COMPOSITE_PROPERTIES: $w('padding-box-width padding-box-height margin-box-width margin-box-height border-box-width border-box-height'),

    COMPUTATIONS: {
      'height': function(element) {
        if (!this._preComputing) this._begin();

        var bHeight = this.get('border-box-height');
        if (bHeight <= 0) {
          if (!this._preComputing) this._end();
          return 0;
        }

        var bTop = this.get('border-top'),
         bBottom = this.get('border-bottom');

        var pTop = this.get('padding-top'),
         pBottom = this.get('padding-bottom');

        if (!this._preComputing) this._end();

        return bHeight - bTop - bBottom - pTop - pBottom;
      },

      'width': function(element) {
        if (!this._preComputing) this._begin();

        var bWidth = this.get('border-box-width');
        if (bWidth <= 0) {
          if (!this._preComputing) this._end();
          return 0;
        }

        var bLeft = this.get('border-left'),
         bRight = this.get('border-right');

        var pLeft = this.get('padding-left'),
         pRight = this.get('padding-right');

        if (!this._preComputing) this._end();

        return bWidth - bLeft - bRight - pLeft - pRight;
      },

      'padding-box-height': function(element) {
        var height = this.get('height'),
         pTop = this.get('padding-top'),
         pBottom = this.get('padding-bottom');

        return height + pTop + pBottom;
      },

      'padding-box-width': function(element) {
        var width = this.get('width'),
         pLeft = this.get('padding-left'),
         pRight = this.get('padding-right');

        return width + pLeft + pRight;
      },

      'border-box-height': function(element) {
        if (!this._preComputing) this._begin();
        var height = element.offsetHeight;
        if (!this._preComputing) this._end();
        return height;
      },

      'border-box-width': function(element) {
        if (!this._preComputing) this._begin();
        var width = element.offsetWidth;
        if (!this._preComputing) this._end();
        return width;
      },

      'margin-box-height': function(element) {
        var bHeight = this.get('border-box-height'),
         mTop = this.get('margin-top'),
         mBottom = this.get('margin-bottom');

        if (bHeight <= 0) return 0;

        return bHeight + mTop + mBottom;
      },

      'margin-box-width': function(element) {
        var bWidth = this.get('border-box-width'),
         mLeft = this.get('margin-left'),
         mRight = this.get('margin-right');

        if (bWidth <= 0) return 0;

        return bWidth + mLeft + mRight;
      },

      'top': function(element) {
        var offset = element.positionedOffset();
        return offset.top;
      },

      'bottom': function(element) {
        var offset = element.positionedOffset(),
         parent = element.getOffsetParent(),
         pHeight = parent.measure('height');

        var mHeight = this.get('border-box-height');

        return pHeight - mHeight - offset.top;
      },

      'left': function(element) {
        var offset = element.positionedOffset();
        return offset.left;
      },

      'right': function(element) {
        var offset = element.positionedOffset(),
         parent = element.getOffsetParent(),
         pWidth = parent.measure('width');

        var mWidth = this.get('border-box-width');

        return pWidth - mWidth - offset.left;
      },

      'padding-top': function(element) {
        return getPixelValue(element, 'paddingTop');
      },

      'padding-bottom': function(element) {
        return getPixelValue(element, 'paddingBottom');
      },

      'padding-left': function(element) {
        return getPixelValue(element, 'paddingLeft');
      },

      'padding-right': function(element) {
        return getPixelValue(element, 'paddingRight');
      },

      'border-top': function(element) {
        return getPixelValue(element, 'borderTopWidth');
      },

      'border-bottom': function(element) {
        return getPixelValue(element, 'borderBottomWidth');
      },

      'border-left': function(element) {
        return getPixelValue(element, 'borderLeftWidth');
      },

      'border-right': function(element) {
        return getPixelValue(element, 'borderRightWidth');
      },

      'margin-top': function(element) {
        return getPixelValue(element, 'marginTop');
      },

      'margin-bottom': function(element) {
        return getPixelValue(element, 'marginBottom');
      },

      'margin-left': function(element) {
        return getPixelValue(element, 'marginLeft');
      },

      'margin-right': function(element) {
        return getPixelValue(element, 'marginRight');
      }
    }
  });

  if ('getBoundingClientRect' in document.documentElement) {
    Object.extend(Element.Layout.COMPUTATIONS, {
      'right': function(element) {
        var parent = hasLayout(element.getOffsetParent());
        var rect = element.getBoundingClientRect(),
         pRect = parent.getBoundingClientRect();

        return (pRect.right - rect.right).round();
      },

      'bottom': function(element) {
        var parent = hasLayout(element.getOffsetParent());
        var rect = element.getBoundingClientRect(),
         pRect = parent.getBoundingClientRect();

        return (pRect.bottom - rect.bottom).round();
      }
    });
  }

  Element.Offset = Class.create({
    initialize: function(left, top) {
      this.left = left.round();
      this.top  = top.round();

      this[0] = this.left;
      this[1] = this.top;
    },

    relativeTo: function(offset) {
      return new Element.Offset(
        this.left - offset.left,
        this.top  - offset.top
      );
    },

    inspect: function() {
      return "#<Element.Offset left: #{left} top: #{top}>".interpolate(this);
    },

    toString: function() {
      return "[#{left}, #{top}]".interpolate(this);
    },

    toArray: function() {
      return [this.left, this.top];
    }
  });

  function getLayout(element, preCompute) {
    return new Element.Layout(element, preCompute);
  }

  function measure(element, property) {
    return $(element).getLayout().get(property);
  }

  function getDimensions(element) {
    element = $(element);
    var display = Element.getStyle(element, 'display');

    if (display && display !== 'none') {
      return { width: element.offsetWidth, height: element.offsetHeight };
    }

    var style = element.style;
    var originalStyles = {
      visibility: style.visibility,
      position:   style.position,
      display:    style.display
    };

    var newStyles = {
      visibility: 'hidden',
      display:    'block'
    };

    if (originalStyles.position !== 'fixed')
      newStyles.position = 'absolute';

    Element.setStyle(element, newStyles);

    var dimensions = {
      width:  element.offsetWidth,
      height: element.offsetHeight
    };

    Element.setStyle(element, originalStyles);

    return dimensions;
  }

  function getOffsetParent(element) {
    element = $(element);

    if (isDocument(element) || isDetached(element) || isBody(element) || isHtml(element))
      return $(document.body);

    var isInline = (Element.getStyle(element, 'display') === 'inline');
    if (!isInline && element.offsetParent) return $(element.offsetParent);

    while ((element = element.parentNode) && element !== document.body) {
      if (Element.getStyle(element, 'position') !== 'static') {
        return isHtml(element) ? $(document.body) : $(element);
      }
    }

    return $(document.body);
  }


  function cumulativeOffset(element) {
    element = $(element);
    var valueT = 0, valueL = 0;
    if (element.parentNode) {
      do {
        valueT += element.offsetTop  || 0;
        valueL += element.offsetLeft || 0;
        element = element.offsetParent;
      } while (element);
    }
    return new Element.Offset(valueL, valueT);
  }

  function positionedOffset(element) {
    element = $(element);

    var layout = element.getLayout();

    var valueT = 0, valueL = 0;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      element = element.offsetParent;
      if (element) {
        if (isBody(element)) break;
        var p = Element.getStyle(element, 'position');
        if (p !== 'static') break;
      }
    } while (element);

    valueL -= layout.get('margin-top');
    valueT -= layout.get('margin-left');

    return new Element.Offset(valueL, valueT);
  }

  function cumulativeScrollOffset(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.scrollTop  || 0;
      valueL += element.scrollLeft || 0;
      element = element.parentNode;
    } while (element);
    return new Element.Offset(valueL, valueT);
  }

  function viewportOffset(forElement) {
    element = $(element);
    var valueT = 0, valueL = 0, docBody = document.body;

    var element = forElement;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      if (element.offsetParent == docBody &&
        Element.getStyle(element, 'position') == 'absolute') break;
    } while (element = element.offsetParent);

    element = forElement;
    do {
      if (element != docBody) {
        valueT -= element.scrollTop  || 0;
        valueL -= element.scrollLeft || 0;
      }
    } while (element = element.parentNode);
    return new Element.Offset(valueL, valueT);
  }

  function absolutize(element) {
    element = $(element);

    if (Element.getStyle(element, 'position') === 'absolute') {
      return element;
    }

    var offsetParent = getOffsetParent(element);
    var eOffset = element.viewportOffset(),
     pOffset = offsetParent.viewportOffset();

    var offset = eOffset.relativeTo(pOffset);
    var layout = element.getLayout();

    element.store('prototype_absolutize_original_styles', {
      left:   element.getStyle('left'),
      top:    element.getStyle('top'),
      width:  element.getStyle('width'),
      height: element.getStyle('height')
    });

    element.setStyle({
      position: 'absolute',
      top:    offset.top + 'px',
      left:   offset.left + 'px',
      width:  layout.get('width') + 'px',
      height: layout.get('height') + 'px'
    });

    return element;
  }

  function relativize(element) {
    element = $(element);
    if (Element.getStyle(element, 'position') === 'relative') {
      return element;
    }

    var originalStyles =
     element.retrieve('prototype_absolutize_original_styles');

    if (originalStyles) element.setStyle(originalStyles);
    return element;
  }

  if (Prototype.Browser.IE) {
    getOffsetParent = getOffsetParent.wrap(
      function(proceed, element) {
        element = $(element);

        if (isDocument(element) || isDetached(element) || isBody(element) || isHtml(element))
          return $(document.body);

        var position = element.getStyle('position');
        if (position !== 'static') return proceed(element);

        element.setStyle({ position: 'relative' });
        var value = proceed(element);
        element.setStyle({ position: position });
        return value;
      }
    );

    positionedOffset = positionedOffset.wrap(function(proceed, element) {
      element = $(element);
      if (!element.parentNode) return new Element.Offset(0, 0);
      var position = element.getStyle('position');
      if (position !== 'static') return proceed(element);

      var offsetParent = element.getOffsetParent();
      if (offsetParent && offsetParent.getStyle('position') === 'fixed')
        hasLayout(offsetParent);

      element.setStyle({ position: 'relative' });
      var value = proceed(element);
      element.setStyle({ position: position });
      return value;
    });
  } else if (Prototype.Browser.Webkit) {
    cumulativeOffset = function(element) {
      element = $(element);
      var valueT = 0, valueL = 0;
      do {
        valueT += element.offsetTop  || 0;
        valueL += element.offsetLeft || 0;
        if (element.offsetParent == document.body)
          if (Element.getStyle(element, 'position') == 'absolute') break;

        element = element.offsetParent;
      } while (element);

      return new Element.Offset(valueL, valueT);
    };
  }


  Element.addMethods({
    getLayout:              getLayout,
    measure:                measure,
    getDimensions:          getDimensions,
    getOffsetParent:        getOffsetParent,
    cumulativeOffset:       cumulativeOffset,
    positionedOffset:       positionedOffset,
    cumulativeScrollOffset: cumulativeScrollOffset,
    viewportOffset:         viewportOffset,
    absolutize:             absolutize,
    relativize:             relativize
  });

  function isBody(element) {
    return element.nodeName.toUpperCase() === 'BODY';
  }

  function isHtml(element) {
    return element.nodeName.toUpperCase() === 'HTML';
  }

  function isDocument(element) {
    return element.nodeType === Node.DOCUMENT_NODE;
  }

  function isDetached(element) {
    return element !== document.body &&
     !Element.descendantOf(element, document.body);
  }

  if ('getBoundingClientRect' in document.documentElement) {
    Element.addMethods({
      viewportOffset: function(element) {
        element = $(element);
        if (isDetached(element)) return new Element.Offset(0, 0);

        var rect = element.getBoundingClientRect(),
         docEl = document.documentElement;
        return new Element.Offset(rect.left - docEl.clientLeft,
         rect.top - docEl.clientTop);
      }
    });
  }
})();
window.$$ = function() {
  var expression = $A(arguments).join(', ');
  return Prototype.Selector.select(expression, document);
};

Prototype.Selector = (function() {

  function select() {
    throw new Error('Method "Prototype.Selector.select" must be defined.');
  }

  function match() {
    throw new Error('Method "Prototype.Selector.match" must be defined.');
  }

  function find(elements, expression, index) {
    index = index || 0;
    var match = Prototype.Selector.match, length = elements.length, matchIndex = 0, i;

    for (i = 0; i < length; i++) {
      if (match(elements[i], expression) && index == matchIndex++) {
        return Element.extend(elements[i]);
      }
    }
  }

  function extendElements(elements) {
    for (var i = 0, length = elements.length; i < length; i++) {
      Element.extend(elements[i]);
    }
    return elements;
  }


  var K = Prototype.K;

  return {
    select: select,
    match: match,
    find: find,
    extendElements: (Element.extend === K) ? K : extendElements,
    extendElement: Element.extend
  };
})();
/*!
 * Sizzle CSS Selector Engine - v1.0
 *  Copyright 2009, The Dojo Foundation
 *  Released under the MIT, BSD, and GPL Licenses.
 *  More information: http://sizzlejs.com/
 */
(function(){

var chunker = /((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^[\]]*\]|['"][^'"]*['"]|[^[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?((?:.|\r|\n)*)/g,
	done = 0,
	toString = Object.prototype.toString,
	hasDuplicate = false,
	baseHasDuplicate = true;

[0, 0].sort(function(){
	baseHasDuplicate = false;
	return 0;
});

var Sizzle = function(selector, context, results, seed) {
	results = results || [];
	var origContext = context = context || document;

	if ( context.nodeType !== 1 && context.nodeType !== 9 ) {
		return [];
	}

	if ( !selector || typeof selector !== "string" ) {
		return results;
	}

	var parts = [], m, set, checkSet, check, mode, extra, prune = true, contextXML = isXML(context),
		soFar = selector;

	while ( (chunker.exec(""), m = chunker.exec(soFar)) !== null ) {
		soFar = m[3];

		parts.push( m[1] );

		if ( m[2] ) {
			extra = m[3];
			break;
		}
	}

	if ( parts.length > 1 && origPOS.exec( selector ) ) {
		if ( parts.length === 2 && Expr.relative[ parts[0] ] ) {
			set = posProcess( parts[0] + parts[1], context );
		} else {
			set = Expr.relative[ parts[0] ] ?
				[ context ] :
				Sizzle( parts.shift(), context );

			while ( parts.length ) {
				selector = parts.shift();

				if ( Expr.relative[ selector ] )
					selector += parts.shift();

				set = posProcess( selector, set );
			}
		}
	} else {
		if ( !seed && parts.length > 1 && context.nodeType === 9 && !contextXML &&
				Expr.match.ID.test(parts[0]) && !Expr.match.ID.test(parts[parts.length - 1]) ) {
			var ret = Sizzle.find( parts.shift(), context, contextXML );
			context = ret.expr ? Sizzle.filter( ret.expr, ret.set )[0] : ret.set[0];
		}

		if ( context ) {
			var ret = seed ?
				{ expr: parts.pop(), set: makeArray(seed) } :
				Sizzle.find( parts.pop(), parts.length === 1 && (parts[0] === "~" || parts[0] === "+") && context.parentNode ? context.parentNode : context, contextXML );
			set = ret.expr ? Sizzle.filter( ret.expr, ret.set ) : ret.set;

			if ( parts.length > 0 ) {
				checkSet = makeArray(set);
			} else {
				prune = false;
			}

			while ( parts.length ) {
				var cur = parts.pop(), pop = cur;

				if ( !Expr.relative[ cur ] ) {
					cur = "";
				} else {
					pop = parts.pop();
				}

				if ( pop == null ) {
					pop = context;
				}

				Expr.relative[ cur ]( checkSet, pop, contextXML );
			}
		} else {
			checkSet = parts = [];
		}
	}

	if ( !checkSet ) {
		checkSet = set;
	}

	if ( !checkSet ) {
		throw "Syntax error, unrecognized expression: " + (cur || selector);
	}

	if ( toString.call(checkSet) === "[object Array]" ) {
		if ( !prune ) {
			results.push.apply( results, checkSet );
		} else if ( context && context.nodeType === 1 ) {
			for ( var i = 0; checkSet[i] != null; i++ ) {
				if ( checkSet[i] && (checkSet[i] === true || checkSet[i].nodeType === 1 && contains(context, checkSet[i])) ) {
					results.push( set[i] );
				}
			}
		} else {
			for ( var i = 0; checkSet[i] != null; i++ ) {
				if ( checkSet[i] && checkSet[i].nodeType === 1 ) {
					results.push( set[i] );
				}
			}
		}
	} else {
		makeArray( checkSet, results );
	}

	if ( extra ) {
		Sizzle( extra, origContext, results, seed );
		Sizzle.uniqueSort( results );
	}

	return results;
};

Sizzle.uniqueSort = function(results){
	if ( sortOrder ) {
		hasDuplicate = baseHasDuplicate;
		results.sort(sortOrder);

		if ( hasDuplicate ) {
			for ( var i = 1; i < results.length; i++ ) {
				if ( results[i] === results[i-1] ) {
					results.splice(i--, 1);
				}
			}
		}
	}

	return results;
};

Sizzle.matches = function(expr, set){
	return Sizzle(expr, null, null, set);
};

Sizzle.find = function(expr, context, isXML){
	var set, match;

	if ( !expr ) {
		return [];
	}

	for ( var i = 0, l = Expr.order.length; i < l; i++ ) {
		var type = Expr.order[i], match;

		if ( (match = Expr.leftMatch[ type ].exec( expr )) ) {
			var left = match[1];
			match.splice(1,1);

			if ( left.substr( left.length - 1 ) !== "\\" ) {
				match[1] = (match[1] || "").replace(/\\/g, "");
				set = Expr.find[ type ]( match, context, isXML );
				if ( set != null ) {
					expr = expr.replace( Expr.match[ type ], "" );
					break;
				}
			}
		}
	}

	if ( !set ) {
		set = context.getElementsByTagName("*");
	}

	return {set: set, expr: expr};
};

Sizzle.filter = function(expr, set, inplace, not){
	var old = expr, result = [], curLoop = set, match, anyFound,
		isXMLFilter = set && set[0] && isXML(set[0]);

	while ( expr && set.length ) {
		for ( var type in Expr.filter ) {
			if ( (match = Expr.match[ type ].exec( expr )) != null ) {
				var filter = Expr.filter[ type ], found, item;
				anyFound = false;

				if ( curLoop == result ) {
					result = [];
				}

				if ( Expr.preFilter[ type ] ) {
					match = Expr.preFilter[ type ]( match, curLoop, inplace, result, not, isXMLFilter );

					if ( !match ) {
						anyFound = found = true;
					} else if ( match === true ) {
						continue;
					}
				}

				if ( match ) {
					for ( var i = 0; (item = curLoop[i]) != null; i++ ) {
						if ( item ) {
							found = filter( item, match, i, curLoop );
							var pass = not ^ !!found;

							if ( inplace && found != null ) {
								if ( pass ) {
									anyFound = true;
								} else {
									curLoop[i] = false;
								}
							} else if ( pass ) {
								result.push( item );
								anyFound = true;
							}
						}
					}
				}

				if ( found !== undefined ) {
					if ( !inplace ) {
						curLoop = result;
					}

					expr = expr.replace( Expr.match[ type ], "" );

					if ( !anyFound ) {
						return [];
					}

					break;
				}
			}
		}

		if ( expr == old ) {
			if ( anyFound == null ) {
				throw "Syntax error, unrecognized expression: " + expr;
			} else {
				break;
			}
		}

		old = expr;
	}

	return curLoop;
};

var Expr = Sizzle.selectors = {
	order: [ "ID", "NAME", "TAG" ],
	match: {
		ID: /#((?:[\w\u00c0-\uFFFF-]|\\.)+)/,
		CLASS: /\.((?:[\w\u00c0-\uFFFF-]|\\.)+)/,
		NAME: /\[name=['"]*((?:[\w\u00c0-\uFFFF-]|\\.)+)['"]*\]/,
		ATTR: /\[\s*((?:[\w\u00c0-\uFFFF-]|\\.)+)\s*(?:(\S?=)\s*(['"]*)(.*?)\3|)\s*\]/,
		TAG: /^((?:[\w\u00c0-\uFFFF\*-]|\\.)+)/,
		CHILD: /:(only|nth|last|first)-child(?:\((even|odd|[\dn+-]*)\))?/,
		POS: /:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^-]|$)/,
		PSEUDO: /:((?:[\w\u00c0-\uFFFF-]|\\.)+)(?:\((['"]*)((?:\([^\)]+\)|[^\2\(\)]*)+)\2\))?/
	},
	leftMatch: {},
	attrMap: {
		"class": "className",
		"for": "htmlFor"
	},
	attrHandle: {
		href: function(elem){
			return elem.getAttribute("href");
		}
	},
	relative: {
		"+": function(checkSet, part, isXML){
			var isPartStr = typeof part === "string",
				isTag = isPartStr && !/\W/.test(part),
				isPartStrNotTag = isPartStr && !isTag;

			if ( isTag && !isXML ) {
				part = part.toUpperCase();
			}

			for ( var i = 0, l = checkSet.length, elem; i < l; i++ ) {
				if ( (elem = checkSet[i]) ) {
					while ( (elem = elem.previousSibling) && elem.nodeType !== 1 ) {}

					checkSet[i] = isPartStrNotTag || elem && elem.nodeName === part ?
						elem || false :
						elem === part;
				}
			}

			if ( isPartStrNotTag ) {
				Sizzle.filter( part, checkSet, true );
			}
		},
		">": function(checkSet, part, isXML){
			var isPartStr = typeof part === "string";

			if ( isPartStr && !/\W/.test(part) ) {
				part = isXML ? part : part.toUpperCase();

				for ( var i = 0, l = checkSet.length; i < l; i++ ) {
					var elem = checkSet[i];
					if ( elem ) {
						var parent = elem.parentNode;
						checkSet[i] = parent.nodeName === part ? parent : false;
					}
				}
			} else {
				for ( var i = 0, l = checkSet.length; i < l; i++ ) {
					var elem = checkSet[i];
					if ( elem ) {
						checkSet[i] = isPartStr ?
							elem.parentNode :
							elem.parentNode === part;
					}
				}

				if ( isPartStr ) {
					Sizzle.filter( part, checkSet, true );
				}
			}
		},
		"": function(checkSet, part, isXML){
			var doneName = done++, checkFn = dirCheck;

			if ( !/\W/.test(part) ) {
				var nodeCheck = part = isXML ? part : part.toUpperCase();
				checkFn = dirNodeCheck;
			}

			checkFn("parentNode", part, doneName, checkSet, nodeCheck, isXML);
		},
		"~": function(checkSet, part, isXML){
			var doneName = done++, checkFn = dirCheck;

			if ( typeof part === "string" && !/\W/.test(part) ) {
				var nodeCheck = part = isXML ? part : part.toUpperCase();
				checkFn = dirNodeCheck;
			}

			checkFn("previousSibling", part, doneName, checkSet, nodeCheck, isXML);
		}
	},
	find: {
		ID: function(match, context, isXML){
			if ( typeof context.getElementById !== "undefined" && !isXML ) {
				var m = context.getElementById(match[1]);
				return m ? [m] : [];
			}
		},
		NAME: function(match, context, isXML){
			if ( typeof context.getElementsByName !== "undefined" ) {
				var ret = [], results = context.getElementsByName(match[1]);

				for ( var i = 0, l = results.length; i < l; i++ ) {
					if ( results[i].getAttribute("name") === match[1] ) {
						ret.push( results[i] );
					}
				}

				return ret.length === 0 ? null : ret;
			}
		},
		TAG: function(match, context){
			return context.getElementsByTagName(match[1]);
		}
	},
	preFilter: {
		CLASS: function(match, curLoop, inplace, result, not, isXML){
			match = " " + match[1].replace(/\\/g, "") + " ";

			if ( isXML ) {
				return match;
			}

			for ( var i = 0, elem; (elem = curLoop[i]) != null; i++ ) {
				if ( elem ) {
					if ( not ^ (elem.className && (" " + elem.className + " ").indexOf(match) >= 0) ) {
						if ( !inplace )
							result.push( elem );
					} else if ( inplace ) {
						curLoop[i] = false;
					}
				}
			}

			return false;
		},
		ID: function(match){
			return match[1].replace(/\\/g, "");
		},
		TAG: function(match, curLoop){
			for ( var i = 0; curLoop[i] === false; i++ ){}
			return curLoop[i] && isXML(curLoop[i]) ? match[1] : match[1].toUpperCase();
		},
		CHILD: function(match){
			if ( match[1] == "nth" ) {
				var test = /(-?)(\d*)n((?:\+|-)?\d*)/.exec(
					match[2] == "even" && "2n" || match[2] == "odd" && "2n+1" ||
					!/\D/.test( match[2] ) && "0n+" + match[2] || match[2]);

				match[2] = (test[1] + (test[2] || 1)) - 0;
				match[3] = test[3] - 0;
			}

			match[0] = done++;

			return match;
		},
		ATTR: function(match, curLoop, inplace, result, not, isXML){
			var name = match[1].replace(/\\/g, "");

			if ( !isXML && Expr.attrMap[name] ) {
				match[1] = Expr.attrMap[name];
			}

			if ( match[2] === "~=" ) {
				match[4] = " " + match[4] + " ";
			}

			return match;
		},
		PSEUDO: function(match, curLoop, inplace, result, not){
			if ( match[1] === "not" ) {
				if ( ( chunker.exec(match[3]) || "" ).length > 1 || /^\w/.test(match[3]) ) {
					match[3] = Sizzle(match[3], null, null, curLoop);
				} else {
					var ret = Sizzle.filter(match[3], curLoop, inplace, true ^ not);
					if ( !inplace ) {
						result.push.apply( result, ret );
					}
					return false;
				}
			} else if ( Expr.match.POS.test( match[0] ) || Expr.match.CHILD.test( match[0] ) ) {
				return true;
			}

			return match;
		},
		POS: function(match){
			match.unshift( true );
			return match;
		}
	},
	filters: {
		enabled: function(elem){
			return elem.disabled === false && elem.type !== "hidden";
		},
		disabled: function(elem){
			return elem.disabled === true;
		},
		checked: function(elem){
			return elem.checked === true;
		},
		selected: function(elem){
			elem.parentNode.selectedIndex;
			return elem.selected === true;
		},
		parent: function(elem){
			return !!elem.firstChild;
		},
		empty: function(elem){
			return !elem.firstChild;
		},
		has: function(elem, i, match){
			return !!Sizzle( match[3], elem ).length;
		},
		header: function(elem){
			return /h\d/i.test( elem.nodeName );
		},
		text: function(elem){
			return "text" === elem.type;
		},
		radio: function(elem){
			return "radio" === elem.type;
		},
		checkbox: function(elem){
			return "checkbox" === elem.type;
		},
		file: function(elem){
			return "file" === elem.type;
		},
		password: function(elem){
			return "password" === elem.type;
		},
		submit: function(elem){
			return "submit" === elem.type;
		},
		image: function(elem){
			return "image" === elem.type;
		},
		reset: function(elem){
			return "reset" === elem.type;
		},
		button: function(elem){
			return "button" === elem.type || elem.nodeName.toUpperCase() === "BUTTON";
		},
		input: function(elem){
			return /input|select|textarea|button/i.test(elem.nodeName);
		}
	},
	setFilters: {
		first: function(elem, i){
			return i === 0;
		},
		last: function(elem, i, match, array){
			return i === array.length - 1;
		},
		even: function(elem, i){
			return i % 2 === 0;
		},
		odd: function(elem, i){
			return i % 2 === 1;
		},
		lt: function(elem, i, match){
			return i < match[3] - 0;
		},
		gt: function(elem, i, match){
			return i > match[3] - 0;
		},
		nth: function(elem, i, match){
			return match[3] - 0 == i;
		},
		eq: function(elem, i, match){
			return match[3] - 0 == i;
		}
	},
	filter: {
		PSEUDO: function(elem, match, i, array){
			var name = match[1], filter = Expr.filters[ name ];

			if ( filter ) {
				return filter( elem, i, match, array );
			} else if ( name === "contains" ) {
				return (elem.textContent || elem.innerText || "").indexOf(match[3]) >= 0;
			} else if ( name === "not" ) {
				var not = match[3];

				for ( var i = 0, l = not.length; i < l; i++ ) {
					if ( not[i] === elem ) {
						return false;
					}
				}

				return true;
			}
		},
		CHILD: function(elem, match){
			var type = match[1], node = elem;
			switch (type) {
				case 'only':
				case 'first':
					while ( (node = node.previousSibling) )  {
						if ( node.nodeType === 1 ) return false;
					}
					if ( type == 'first') return true;
					node = elem;
				case 'last':
					while ( (node = node.nextSibling) )  {
						if ( node.nodeType === 1 ) return false;
					}
					return true;
				case 'nth':
					var first = match[2], last = match[3];

					if ( first == 1 && last == 0 ) {
						return true;
					}

					var doneName = match[0],
						parent = elem.parentNode;

					if ( parent && (parent.sizcache !== doneName || !elem.nodeIndex) ) {
						var count = 0;
						for ( node = parent.firstChild; node; node = node.nextSibling ) {
							if ( node.nodeType === 1 ) {
								node.nodeIndex = ++count;
							}
						}
						parent.sizcache = doneName;
					}

					var diff = elem.nodeIndex - last;
					if ( first == 0 ) {
						return diff == 0;
					} else {
						return ( diff % first == 0 && diff / first >= 0 );
					}
			}
		},
		ID: function(elem, match){
			return elem.nodeType === 1 && elem.getAttribute("id") === match;
		},
		TAG: function(elem, match){
			return (match === "*" && elem.nodeType === 1) || elem.nodeName === match;
		},
		CLASS: function(elem, match){
			return (" " + (elem.className || elem.getAttribute("class")) + " ")
				.indexOf( match ) > -1;
		},
		ATTR: function(elem, match){
			var name = match[1],
				result = Expr.attrHandle[ name ] ?
					Expr.attrHandle[ name ]( elem ) :
					elem[ name ] != null ?
						elem[ name ] :
						elem.getAttribute( name ),
				value = result + "",
				type = match[2],
				check = match[4];

			return result == null ?
				type === "!=" :
				type === "=" ?
				value === check :
				type === "*=" ?
				value.indexOf(check) >= 0 :
				type === "~=" ?
				(" " + value + " ").indexOf(check) >= 0 :
				!check ?
				value && result !== false :
				type === "!=" ?
				value != check :
				type === "^=" ?
				value.indexOf(check) === 0 :
				type === "$=" ?
				value.substr(value.length - check.length) === check :
				type === "|=" ?
				value === check || value.substr(0, check.length + 1) === check + "-" :
				false;
		},
		POS: function(elem, match, i, array){
			var name = match[2], filter = Expr.setFilters[ name ];

			if ( filter ) {
				return filter( elem, i, match, array );
			}
		}
	}
};

var origPOS = Expr.match.POS;

for ( var type in Expr.match ) {
	Expr.match[ type ] = new RegExp( Expr.match[ type ].source + /(?![^\[]*\])(?![^\(]*\))/.source );
	Expr.leftMatch[ type ] = new RegExp( /(^(?:.|\r|\n)*?)/.source + Expr.match[ type ].source );
}

var makeArray = function(array, results) {
	array = Array.prototype.slice.call( array, 0 );

	if ( results ) {
		results.push.apply( results, array );
		return results;
	}

	return array;
};

try {
	Array.prototype.slice.call( document.documentElement.childNodes, 0 );

} catch(e){
	makeArray = function(array, results) {
		var ret = results || [];

		if ( toString.call(array) === "[object Array]" ) {
			Array.prototype.push.apply( ret, array );
		} else {
			if ( typeof array.length === "number" ) {
				for ( var i = 0, l = array.length; i < l; i++ ) {
					ret.push( array[i] );
				}
			} else {
				for ( var i = 0; array[i]; i++ ) {
					ret.push( array[i] );
				}
			}
		}

		return ret;
	};
}

var sortOrder;

if ( document.documentElement.compareDocumentPosition ) {
	sortOrder = function( a, b ) {
		if ( !a.compareDocumentPosition || !b.compareDocumentPosition ) {
			if ( a == b ) {
				hasDuplicate = true;
			}
			return 0;
		}

		var ret = a.compareDocumentPosition(b) & 4 ? -1 : a === b ? 0 : 1;
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
} else if ( "sourceIndex" in document.documentElement ) {
	sortOrder = function( a, b ) {
		if ( !a.sourceIndex || !b.sourceIndex ) {
			if ( a == b ) {
				hasDuplicate = true;
			}
			return 0;
		}

		var ret = a.sourceIndex - b.sourceIndex;
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
} else if ( document.createRange ) {
	sortOrder = function( a, b ) {
		if ( !a.ownerDocument || !b.ownerDocument ) {
			if ( a == b ) {
				hasDuplicate = true;
			}
			return 0;
		}

		var aRange = a.ownerDocument.createRange(), bRange = b.ownerDocument.createRange();
		aRange.setStart(a, 0);
		aRange.setEnd(a, 0);
		bRange.setStart(b, 0);
		bRange.setEnd(b, 0);
		var ret = aRange.compareBoundaryPoints(Range.START_TO_END, bRange);
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
}

(function(){
	var form = document.createElement("div"),
		id = "script" + (new Date).getTime();
	form.innerHTML = "<a name='" + id + "'/>";

	var root = document.documentElement;
	root.insertBefore( form, root.firstChild );

	if ( !!document.getElementById( id ) ) {
		Expr.find.ID = function(match, context, isXML){
			if ( typeof context.getElementById !== "undefined" && !isXML ) {
				var m = context.getElementById(match[1]);
				return m ? m.id === match[1] || typeof m.getAttributeNode !== "undefined" && m.getAttributeNode("id").nodeValue === match[1] ? [m] : undefined : [];
			}
		};

		Expr.filter.ID = function(elem, match){
			var node = typeof elem.getAttributeNode !== "undefined" && elem.getAttributeNode("id");
			return elem.nodeType === 1 && node && node.nodeValue === match;
		};
	}

	root.removeChild( form );
	root = form = null; // release memory in IE
})();

(function(){

	var div = document.createElement("div");
	div.appendChild( document.createComment("") );

	if ( div.getElementsByTagName("*").length > 0 ) {
		Expr.find.TAG = function(match, context){
			var results = context.getElementsByTagName(match[1]);

			if ( match[1] === "*" ) {
				var tmp = [];

				for ( var i = 0; results[i]; i++ ) {
					if ( results[i].nodeType === 1 ) {
						tmp.push( results[i] );
					}
				}

				results = tmp;
			}

			return results;
		};
	}

	div.innerHTML = "<a href='#'></a>";
	if ( div.firstChild && typeof div.firstChild.getAttribute !== "undefined" &&
			div.firstChild.getAttribute("href") !== "#" ) {
		Expr.attrHandle.href = function(elem){
			return elem.getAttribute("href", 2);
		};
	}

	div = null; // release memory in IE
})();

if ( document.querySelectorAll ) (function(){
	var oldSizzle = Sizzle, div = document.createElement("div");
	div.innerHTML = "<p class='TEST'></p>";

	if ( div.querySelectorAll && div.querySelectorAll(".TEST").length === 0 ) {
		return;
	}

	Sizzle = function(query, context, extra, seed){
		context = context || document;

		if ( !seed && context.nodeType === 9 && !isXML(context) ) {
			try {
				return makeArray( context.querySelectorAll(query), extra );
			} catch(e){}
		}

		return oldSizzle(query, context, extra, seed);
	};

	for ( var prop in oldSizzle ) {
		Sizzle[ prop ] = oldSizzle[ prop ];
	}

	div = null; // release memory in IE
})();

if ( document.getElementsByClassName && document.documentElement.getElementsByClassName ) (function(){
	var div = document.createElement("div");
	div.innerHTML = "<div class='test e'></div><div class='test'></div>";

	if ( div.getElementsByClassName("e").length === 0 )
		return;

	div.lastChild.className = "e";

	if ( div.getElementsByClassName("e").length === 1 )
		return;

	Expr.order.splice(1, 0, "CLASS");
	Expr.find.CLASS = function(match, context, isXML) {
		if ( typeof context.getElementsByClassName !== "undefined" && !isXML ) {
			return context.getElementsByClassName(match[1]);
		}
	};

	div = null; // release memory in IE
})();

function dirNodeCheck( dir, cur, doneName, checkSet, nodeCheck, isXML ) {
	var sibDir = dir == "previousSibling" && !isXML;
	for ( var i = 0, l = checkSet.length; i < l; i++ ) {
		var elem = checkSet[i];
		if ( elem ) {
			if ( sibDir && elem.nodeType === 1 ){
				elem.sizcache = doneName;
				elem.sizset = i;
			}
			elem = elem[dir];
			var match = false;

			while ( elem ) {
				if ( elem.sizcache === doneName ) {
					match = checkSet[elem.sizset];
					break;
				}

				if ( elem.nodeType === 1 && !isXML ){
					elem.sizcache = doneName;
					elem.sizset = i;
				}

				if ( elem.nodeName === cur ) {
					match = elem;
					break;
				}

				elem = elem[dir];
			}

			checkSet[i] = match;
		}
	}
}

function dirCheck( dir, cur, doneName, checkSet, nodeCheck, isXML ) {
	var sibDir = dir == "previousSibling" && !isXML;
	for ( var i = 0, l = checkSet.length; i < l; i++ ) {
		var elem = checkSet[i];
		if ( elem ) {
			if ( sibDir && elem.nodeType === 1 ) {
				elem.sizcache = doneName;
				elem.sizset = i;
			}
			elem = elem[dir];
			var match = false;

			while ( elem ) {
				if ( elem.sizcache === doneName ) {
					match = checkSet[elem.sizset];
					break;
				}

				if ( elem.nodeType === 1 ) {
					if ( !isXML ) {
						elem.sizcache = doneName;
						elem.sizset = i;
					}
					if ( typeof cur !== "string" ) {
						if ( elem === cur ) {
							match = true;
							break;
						}

					} else if ( Sizzle.filter( cur, [elem] ).length > 0 ) {
						match = elem;
						break;
					}
				}

				elem = elem[dir];
			}

			checkSet[i] = match;
		}
	}
}

var contains = document.compareDocumentPosition ?  function(a, b){
	return a.compareDocumentPosition(b) & 16;
} : function(a, b){
	return a !== b && (a.contains ? a.contains(b) : true);
};

var isXML = function(elem){
	return elem.nodeType === 9 && elem.documentElement.nodeName !== "HTML" ||
		!!elem.ownerDocument && elem.ownerDocument.documentElement.nodeName !== "HTML";
};

var posProcess = function(selector, context){
	var tmpSet = [], later = "", match,
		root = context.nodeType ? [context] : context;

	while ( (match = Expr.match.PSEUDO.exec( selector )) ) {
		later += match[0];
		selector = selector.replace( Expr.match.PSEUDO, "" );
	}

	selector = Expr.relative[selector] ? selector + "*" : selector;

	for ( var i = 0, l = root.length; i < l; i++ ) {
		Sizzle( selector, root[i], tmpSet );
	}

	return Sizzle.filter( later, tmpSet );
};


window.Sizzle = Sizzle;

})();

Prototype._original_property = window.Sizzle;

;(function(engine) {
  var extendElements = Prototype.Selector.extendElements;

  function select(selector, scope) {
    return extendElements(engine(selector, scope || document));
  }

  function match(element, selector) {
    return engine.matches(selector, [element]).length == 1;
  }

  Prototype.Selector.engine = engine;
  Prototype.Selector.select = select;
  Prototype.Selector.match = match;
})(Sizzle);

window.Sizzle = Prototype._original_property;
delete Prototype._original_property;

var Form = {
  reset: function(form) {
    form = $(form);
    form.reset();
    return form;
  },

  serializeElements: function(elements, options) {
    if (typeof options != 'object') options = { hash: !!options };
    else if (Object.isUndefined(options.hash)) options.hash = true;
    var key, value, submitted = false, submit = options.submit, accumulator, initial;

    if (options.hash) {
      initial = {};
      accumulator = function(result, key, value) {
        if (key in result) {
          if (!Object.isArray(result[key])) result[key] = [result[key]];
          result[key].push(value);
        } else result[key] = value;
        return result;
      };
    } else {
      initial = '';
      accumulator = function(result, key, value) {
        return result + (result ? '&' : '') + encodeURIComponent(key) + '=' + encodeURIComponent(value);
      }
    }

    return elements.inject(initial, function(result, element) {
      if (!element.disabled && element.name) {
        key = element.name; value = $(element).getValue();
        if (value != null && element.type != 'file' && (element.type != 'submit' || (!submitted &&
            submit !== false && (!submit || key == submit) && (submitted = true)))) {
          result = accumulator(result, key, value);
        }
      }
      return result;
    });
  }
};

Form.Methods = {
  serialize: function(form, options) {
    return Form.serializeElements(Form.getElements(form), options);
  },

  getElements: function(form) {
    var elements = $(form).getElementsByTagName('*'),
        element,
        arr = [ ],
        serializers = Form.Element.Serializers;
    for (var i = 0; element = elements[i]; i++) {
      arr.push(element);
    }
    return arr.inject([], function(elements, child) {
      if (serializers[child.tagName.toLowerCase()])
        elements.push(Element.extend(child));
      return elements;
    })
  },

  getInputs: function(form, typeName, name) {
    form = $(form);
    var inputs = form.getElementsByTagName('input');

    if (!typeName && !name) return $A(inputs).map(Element.extend);

    for (var i = 0, matchingInputs = [], length = inputs.length; i < length; i++) {
      var input = inputs[i];
      if ((typeName && input.type != typeName) || (name && input.name != name))
        continue;
      matchingInputs.push(Element.extend(input));
    }

    return matchingInputs;
  },

  disable: function(form) {
    form = $(form);
    Form.getElements(form).invoke('disable');
    return form;
  },

  enable: function(form) {
    form = $(form);
    Form.getElements(form).invoke('enable');
    return form;
  },

  findFirstElement: function(form) {
    var elements = $(form).getElements().findAll(function(element) {
      return 'hidden' != element.type && !element.disabled;
    });
    var firstByIndex = elements.findAll(function(element) {
      return element.hasAttribute('tabIndex') && element.tabIndex >= 0;
    }).sortBy(function(element) { return element.tabIndex }).first();

    return firstByIndex ? firstByIndex : elements.find(function(element) {
      return /^(?:input|select|textarea)$/i.test(element.tagName);
    });
  },

  focusFirstElement: function(form) {
    form = $(form);
    var element = form.findFirstElement();
    if (element) element.activate();
    return form;
  },

  request: function(form, options) {
    form = $(form), options = Object.clone(options || { });

    var params = options.parameters, action = form.readAttribute('action') || '';
    if (action.blank()) action = window.location.href;
    options.parameters = form.serialize(true);

    if (params) {
      if (Object.isString(params)) params = params.toQueryParams();
      Object.extend(options.parameters, params);
    }

    if (form.hasAttribute('method') && !options.method)
      options.method = form.method;

    return new Ajax.Request(action, options);
  }
};

/*--------------------------------------------------------------------------*/


Form.Element = {
  focus: function(element) {
    $(element).focus();
    return element;
  },

  select: function(element) {
    $(element).select();
    return element;
  }
};

Form.Element.Methods = {

  serialize: function(element) {
    element = $(element);
    if (!element.disabled && element.name) {
      var value = element.getValue();
      if (value != undefined) {
        var pair = { };
        pair[element.name] = value;
        return Object.toQueryString(pair);
      }
    }
    return '';
  },

  getValue: function(element) {
    element = $(element);
    var method = element.tagName.toLowerCase();
    return Form.Element.Serializers[method](element);
  },

  setValue: function(element, value) {
    element = $(element);
    var method = element.tagName.toLowerCase();
    Form.Element.Serializers[method](element, value);
    return element;
  },

  clear: function(element) {
    $(element).value = '';
    return element;
  },

  present: function(element) {
    return $(element).value != '';
  },

  activate: function(element) {
    element = $(element);
    try {
      element.focus();
      if (element.select && (element.tagName.toLowerCase() != 'input' ||
          !(/^(?:button|reset|submit)$/i.test(element.type))))
        element.select();
    } catch (e) { }
    return element;
  },

  disable: function(element) {
    element = $(element);
    element.disabled = true;
    return element;
  },

  enable: function(element) {
    element = $(element);
    element.disabled = false;
    return element;
  }
};

/*--------------------------------------------------------------------------*/

var Field = Form.Element;

var $F = Form.Element.Methods.getValue;

/*--------------------------------------------------------------------------*/

Form.Element.Serializers = (function() {
  function input(element, value) {
    switch (element.type.toLowerCase()) {
      case 'checkbox':
      case 'radio':
        return inputSelector(element, value);
      default:
        return valueSelector(element, value);
    }
  }

  function inputSelector(element, value) {
    if (Object.isUndefined(value))
      return element.checked ? element.value : null;
    else element.checked = !!value;
  }

  function valueSelector(element, value) {
    if (Object.isUndefined(value)) return element.value;
    else element.value = value;
  }

  function select(element, value) {
    if (Object.isUndefined(value))
      return (element.type === 'select-one' ? selectOne : selectMany)(element);

    var opt, currentValue, single = !Object.isArray(value);
    for (var i = 0, length = element.length; i < length; i++) {
      opt = element.options[i];
      currentValue = this.optionValue(opt);
      if (single) {
        if (currentValue == value) {
          opt.selected = true;
          return;
        }
      }
      else opt.selected = value.include(currentValue);
    }
  }

  function selectOne(element) {
    var index = element.selectedIndex;
    return index >= 0 ? optionValue(element.options[index]) : null;
  }

  function selectMany(element) {
    var values, length = element.length;
    if (!length) return null;

    for (var i = 0, values = []; i < length; i++) {
      var opt = element.options[i];
      if (opt.selected) values.push(optionValue(opt));
    }
    return values;
  }

  function optionValue(opt) {
    return Element.hasAttribute(opt, 'value') ? opt.value : opt.text;
  }

  return {
    input:         input,
    inputSelector: inputSelector,
    textarea:      valueSelector,
    select:        select,
    selectOne:     selectOne,
    selectMany:    selectMany,
    optionValue:   optionValue,
    button:        valueSelector
  };
})();

/*--------------------------------------------------------------------------*/


Abstract.TimedObserver = Class.create(PeriodicalExecuter, {
  initialize: function($super, element, frequency, callback) {
    $super(callback, frequency);
    this.element   = $(element);
    this.lastValue = this.getValue();
  },

  execute: function() {
    var value = this.getValue();
    if (Object.isString(this.lastValue) && Object.isString(value) ?
        this.lastValue != value : String(this.lastValue) != String(value)) {
      this.callback(this.element, value);
      this.lastValue = value;
    }
  }
});

Form.Element.Observer = Class.create(Abstract.TimedObserver, {
  getValue: function() {
    return Form.Element.getValue(this.element);
  }
});

Form.Observer = Class.create(Abstract.TimedObserver, {
  getValue: function() {
    return Form.serialize(this.element);
  }
});

/*--------------------------------------------------------------------------*/

Abstract.EventObserver = Class.create({
  initialize: function(element, callback) {
    this.element  = $(element);
    this.callback = callback;

    this.lastValue = this.getValue();
    if (this.element.tagName.toLowerCase() == 'form')
      this.registerFormCallbacks();
    else
      this.registerCallback(this.element);
  },

  onElementEvent: function() {
    var value = this.getValue();
    if (this.lastValue != value) {
      this.callback(this.element, value);
      this.lastValue = value;
    }
  },

  registerFormCallbacks: function() {
    Form.getElements(this.element).each(this.registerCallback, this);
  },

  registerCallback: function(element) {
    if (element.type) {
      switch (element.type.toLowerCase()) {
        case 'checkbox':
        case 'radio':
          Event.observe(element, 'click', this.onElementEvent.bind(this));
          break;
        default:
          Event.observe(element, 'change', this.onElementEvent.bind(this));
          break;
      }
    }
  }
});

Form.Element.EventObserver = Class.create(Abstract.EventObserver, {
  getValue: function() {
    return Form.Element.getValue(this.element);
  }
});

Form.EventObserver = Class.create(Abstract.EventObserver, {
  getValue: function() {
    return Form.serialize(this.element);
  }
});
(function() {

  var Event = {
    KEY_BACKSPACE: 8,
    KEY_TAB:       9,
    KEY_RETURN:   13,
    KEY_ESC:      27,
    KEY_LEFT:     37,
    KEY_UP:       38,
    KEY_RIGHT:    39,
    KEY_DOWN:     40,
    KEY_DELETE:   46,
    KEY_HOME:     36,
    KEY_END:      35,
    KEY_PAGEUP:   33,
    KEY_PAGEDOWN: 34,
    KEY_INSERT:   45,

    cache: {}
  };

  var docEl = document.documentElement;
  var MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED = 'onmouseenter' in docEl
    && 'onmouseleave' in docEl;



  var isIELegacyEvent = function(event) { return false; };

  if (window.attachEvent) {
    if (window.addEventListener) {
      isIELegacyEvent = function(event) {
        return !(event instanceof window.Event);
      };
    } else {
      isIELegacyEvent = function(event) { return true; };
    }
  }

  var _isButton;

  function _isButtonForDOMEvents(event, code) {
    return event.which ? (event.which === code + 1) : (event.button === code);
  }

  var legacyButtonMap = { 0: 1, 1: 4, 2: 2 };
  function _isButtonForLegacyEvents(event, code) {
    return event.button === legacyButtonMap[code];
  }

  function _isButtonForWebKit(event, code) {
    switch (code) {
      case 0: return event.which == 1 && !event.metaKey;
      case 1: return event.which == 2 || (event.which == 1 && event.metaKey);
      case 2: return event.which == 3;
      default: return false;
    }
  }

  if (window.attachEvent) {
    if (!window.addEventListener) {
      _isButton = _isButtonForLegacyEvents;
    } else {
      _isButton = function(event, code) {
        return isIELegacyEvent(event) ? _isButtonForLegacyEvents(event, code) :
         _isButtonForDOMEvents(event, code);
      }
    }
  } else if (Prototype.Browser.WebKit) {
    _isButton = _isButtonForWebKit;
  } else {
    _isButton = _isButtonForDOMEvents;
  }

  function isLeftClick(event)   { return _isButton(event, 0) }

  function isMiddleClick(event) { return _isButton(event, 1) }

  function isRightClick(event)  { return _isButton(event, 2) }

  function element(event) {
    event = Event.extend(event);

    var node = event.target, type = event.type,
     currentTarget = event.currentTarget;

    if (currentTarget && currentTarget.tagName) {
      if (type === 'load' || type === 'error' ||
        (type === 'click' && currentTarget.tagName.toLowerCase() === 'input'
          && currentTarget.type === 'radio'))
            node = currentTarget;
    }

    if (node.nodeType == Node.TEXT_NODE)
      node = node.parentNode;

    return Element.extend(node);
  }

  function findElement(event, expression) {
    var element = Event.element(event);

    if (!expression) return element;
    while (element) {
      if (Object.isElement(element) && Prototype.Selector.match(element, expression)) {
        return Element.extend(element);
      }
      element = element.parentNode;
    }
  }

  function pointer(event) {
    return { x: pointerX(event), y: pointerY(event) };
  }

  function pointerX(event) {
    var docElement = document.documentElement,
     body = document.body || { scrollLeft: 0 };

    return event.pageX || (event.clientX +
      (docElement.scrollLeft || body.scrollLeft) -
      (docElement.clientLeft || 0));
  }

  function pointerY(event) {
    var docElement = document.documentElement,
     body = document.body || { scrollTop: 0 };

    return  event.pageY || (event.clientY +
       (docElement.scrollTop || body.scrollTop) -
       (docElement.clientTop || 0));
  }


  function stop(event) {
    Event.extend(event);
    event.preventDefault();
    event.stopPropagation();

    event.stopped = true;
  }


  Event.Methods = {
    isLeftClick:   isLeftClick,
    isMiddleClick: isMiddleClick,
    isRightClick:  isRightClick,

    element:     element,
    findElement: findElement,

    pointer:  pointer,
    pointerX: pointerX,
    pointerY: pointerY,

    stop: stop
  };

  var methods = Object.keys(Event.Methods).inject({ }, function(m, name) {
    m[name] = Event.Methods[name].methodize();
    return m;
  });

  if (window.attachEvent) {
    function _relatedTarget(event) {
      var element;
      switch (event.type) {
        case 'mouseover':
        case 'mouseenter':
          element = event.fromElement;
          break;
        case 'mouseout':
        case 'mouseleave':
          element = event.toElement;
          break;
        default:
          return null;
      }
      return Element.extend(element);
    }

    var additionalMethods = {
      stopPropagation: function() { this.cancelBubble = true },
      preventDefault:  function() { this.returnValue = false },
      inspect: function() { return '[object Event]' }
    };

    Event.extend = function(event, element) {
      if (!event) return false;

      if (!isIELegacyEvent(event)) return event;

      if (event._extendedByPrototype) return event;
      event._extendedByPrototype = Prototype.emptyFunction;

      var pointer = Event.pointer(event);

      Object.extend(event, {
        target: event.srcElement || element,
        relatedTarget: _relatedTarget(event),
        pageX:  pointer.x,
        pageY:  pointer.y
      });

      Object.extend(event, methods);
      Object.extend(event, additionalMethods);

      return event;
    };
  } else {
    Event.extend = Prototype.K;
  }

  if (window.addEventListener) {
    Event.prototype = window.Event.prototype || document.createEvent('HTMLEvents').__proto__;
    Object.extend(Event.prototype, methods);
  }

  function _createResponder(element, eventName, handler) {
    var registry = Element.retrieve(element, 'prototype_event_registry');

    if (Object.isUndefined(registry)) {
      CACHE.push(element);
      registry = Element.retrieve(element, 'prototype_event_registry', $H());
    }

    var respondersForEvent = registry.get(eventName);
    if (Object.isUndefined(respondersForEvent)) {
      respondersForEvent = [];
      registry.set(eventName, respondersForEvent);
    }

    if (respondersForEvent.pluck('handler').include(handler)) return false;

    var responder;
    if (eventName.include(":")) {
      responder = function(event) {
        if (Object.isUndefined(event.eventName))
          return false;

        if (event.eventName !== eventName)
          return false;

        Event.extend(event, element);
        handler.call(element, event);
      };
    } else {
      if (!MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED &&
       (eventName === "mouseenter" || eventName === "mouseleave")) {
        if (eventName === "mouseenter" || eventName === "mouseleave") {
          responder = function(event) {
            Event.extend(event, element);

            var parent = event.relatedTarget;
            while (parent && parent !== element) {
              try { parent = parent.parentNode; }
              catch(e) { parent = element; }
            }

            if (parent === element) return;

            handler.call(element, event);
          };
        }
      } else {
        responder = function(event) {
          Event.extend(event, element);
          handler.call(element, event);
        };
      }
    }

    responder.handler = handler;
    respondersForEvent.push(responder);
    return responder;
  }

  function _destroyCache() {
    for (var i = 0, length = CACHE.length; i < length; i++) {
      Event.stopObserving(CACHE[i]);
      CACHE[i] = null;
    }
  }

  var CACHE = [];

  if (Prototype.Browser.IE)
    window.attachEvent('onunload', _destroyCache);

  if (Prototype.Browser.WebKit)
    window.addEventListener('unload', Prototype.emptyFunction, false);


  var _getDOMEventName = Prototype.K,
      translations = { mouseenter: "mouseover", mouseleave: "mouseout" };

  if (!MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED) {
    _getDOMEventName = function(eventName) {
      return (translations[eventName] || eventName);
    };
  }

  function observe(element, eventName, handler) {
    element = $(element);

    var responder = _createResponder(element, eventName, handler);

    if (!responder) return element;

    if (eventName.include(':')) {
      if (element.addEventListener)
        element.addEventListener("dataavailable", responder, false);
      else {
        element.attachEvent("ondataavailable", responder);
        element.attachEvent("onlosecapture", responder);
      }
    } else {
      var actualEventName = _getDOMEventName(eventName);

      if (element.addEventListener)
        element.addEventListener(actualEventName, responder, false);
      else
        element.attachEvent("on" + actualEventName, responder);
    }

    return element;
  }

  function stopObserving(element, eventName, handler) {
    element = $(element);

    var registry = Element.retrieve(element, 'prototype_event_registry');
    if (!registry) return element;

    if (!eventName) {
      registry.each( function(pair) {
        var eventName = pair.key;
        stopObserving(element, eventName);
      });
      return element;
    }

    var responders = registry.get(eventName);
    if (!responders) return element;

    if (!handler) {
      responders.each(function(r) {
        stopObserving(element, eventName, r.handler);
      });
      return element;
    }

    var i = responders.length, responder;
    while (i--) {
      if (responders[i].handler === handler) {
        responder = responders[i];
        break;
      }
    }
    if (!responder) return element;

    if (eventName.include(':')) {
      if (element.removeEventListener)
        element.removeEventListener("dataavailable", responder, false);
      else {
        element.detachEvent("ondataavailable", responder);
        element.detachEvent("onlosecapture", responder);
      }
    } else {
      var actualEventName = _getDOMEventName(eventName);
      if (element.removeEventListener)
        element.removeEventListener(actualEventName, responder, false);
      else
        element.detachEvent('on' + actualEventName, responder);
    }

    registry.set(eventName, responders.without(responder));

    return element;
  }

  function fire(element, eventName, memo, bubble) {
    element = $(element);

    if (Object.isUndefined(bubble))
      bubble = true;

    if (element == document && document.createEvent && !element.dispatchEvent)
      element = document.documentElement;

    var event;
    if (document.createEvent) {
      event = document.createEvent('HTMLEvents');
      event.initEvent('dataavailable', bubble, true);
    } else {
      event = document.createEventObject();
      event.eventType = bubble ? 'ondataavailable' : 'onlosecapture';
    }

    event.eventName = eventName;
    event.memo = memo || { };

    if (document.createEvent)
      element.dispatchEvent(event);
    else
      element.fireEvent(event.eventType, event);

    return Event.extend(event);
  }

  Event.Handler = Class.create({
    initialize: function(element, eventName, selector, callback) {
      this.element   = $(element);
      this.eventName = eventName;
      this.selector  = selector;
      this.callback  = callback;
      this.handler   = this.handleEvent.bind(this);
    },

    start: function() {
      Event.observe(this.element, this.eventName, this.handler);
      return this;
    },

    stop: function() {
      Event.stopObserving(this.element, this.eventName, this.handler);
      return this;
    },

    handleEvent: function(event) {
      var element = Event.findElement(event, this.selector);
      if (element) this.callback.call(this.element, event, element);
    }
  });

  function on(element, eventName, selector, callback) {
    element = $(element);
    if (Object.isFunction(selector) && Object.isUndefined(callback)) {
      callback = selector, selector = null;
    }

    return new Event.Handler(element, eventName, selector, callback).start();
  }

  Object.extend(Event, Event.Methods);

  Object.extend(Event, {
    fire:          fire,
    observe:       observe,
    stopObserving: stopObserving,
    on:            on
  });

  Element.addMethods({
    fire:          fire,

    observe:       observe,

    stopObserving: stopObserving,

    on:            on
  });

  Object.extend(document, {
    fire:          fire.methodize(),

    observe:       observe.methodize(),

    stopObserving: stopObserving.methodize(),

    on:            on.methodize(),

    loaded:        false
  });

  if (window.Event) Object.extend(window.Event, Event);
  else window.Event = Event;
})();

(function() {
  /* Support for the DOMContentLoaded event is based on work by Dan Webb,
     Matthias Miller, Dean Edwards, John Resig, and Diego Perini. */

  var timer;

  function fireContentLoadedEvent() {
    if (document.loaded) return;
    if (timer) window.clearTimeout(timer);
    document.loaded = true;
    document.fire('dom:loaded');
  }

  function checkReadyState() {
    if (document.readyState === 'complete') {
      document.stopObserving('readystatechange', checkReadyState);
      fireContentLoadedEvent();
    }
  }

  function pollDoScroll() {
    try { document.documentElement.doScroll('left'); }
    catch(e) {
      timer = pollDoScroll.defer();
      return;
    }
    fireContentLoadedEvent();
  }

  if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', fireContentLoadedEvent, false);
  } else {
    document.observe('readystatechange', checkReadyState);
    if (window == top)
      timer = pollDoScroll.defer();
  }

  Event.observe(window, 'load', fireContentLoadedEvent);
})();


Element.addMethods();
/*------------------------------- DEPRECATED -------------------------------*/

Hash.toQueryString = Object.toQueryString;

var Toggle = { display: Element.toggle };

Element.Methods.childOf = Element.Methods.descendantOf;

var Insertion = {
  Before: function(element, content) {
    return Element.insert(element, {before:content});
  },

  Top: function(element, content) {
    return Element.insert(element, {top:content});
  },

  Bottom: function(element, content) {
    return Element.insert(element, {bottom:content});
  },

  After: function(element, content) {
    return Element.insert(element, {after:content});
  }
};

var $continue = new Error('"throw $continue" is deprecated, use "return" instead');

var Position = {
  includeScrollOffsets: false,

  prepare: function() {
    this.deltaX =  window.pageXOffset
                || document.documentElement.scrollLeft
                || document.body.scrollLeft
                || 0;
    this.deltaY =  window.pageYOffset
                || document.documentElement.scrollTop
                || document.body.scrollTop
                || 0;
  },

  within: function(element, x, y) {
    if (this.includeScrollOffsets)
      return this.withinIncludingScrolloffsets(element, x, y);
    this.xcomp = x;
    this.ycomp = y;
    this.offset = Element.cumulativeOffset(element);

    return (y >= this.offset[1] &&
            y <  this.offset[1] + element.offsetHeight &&
            x >= this.offset[0] &&
            x <  this.offset[0] + element.offsetWidth);
  },

  withinIncludingScrolloffsets: function(element, x, y) {
    var offsetcache = Element.cumulativeScrollOffset(element);

    this.xcomp = x + offsetcache[0] - this.deltaX;
    this.ycomp = y + offsetcache[1] - this.deltaY;
    this.offset = Element.cumulativeOffset(element);

    return (this.ycomp >= this.offset[1] &&
            this.ycomp <  this.offset[1] + element.offsetHeight &&
            this.xcomp >= this.offset[0] &&
            this.xcomp <  this.offset[0] + element.offsetWidth);
  },

  overlap: function(mode, element) {
    if (!mode) return 0;
    if (mode == 'vertical')
      return ((this.offset[1] + element.offsetHeight) - this.ycomp) /
        element.offsetHeight;
    if (mode == 'horizontal')
      return ((this.offset[0] + element.offsetWidth) - this.xcomp) /
        element.offsetWidth;
  },


  cumulativeOffset: Element.Methods.cumulativeOffset,

  positionedOffset: Element.Methods.positionedOffset,

  absolutize: function(element) {
    Position.prepare();
    return Element.absolutize(element);
  },

  relativize: function(element) {
    Position.prepare();
    return Element.relativize(element);
  },

  realOffset: Element.Methods.cumulativeScrollOffset,

  offsetParent: Element.Methods.getOffsetParent,

  page: Element.Methods.viewportOffset,

  clone: function(source, target, options) {
    options = options || { };
    return Element.clonePosition(target, source, options);
  }
};

/*--------------------------------------------------------------------------*/

if (!document.getElementsByClassName) document.getElementsByClassName = function(instanceMethods){
  function iter(name) {
    return name.blank() ? null : "[contains(concat(' ', @class, ' '), ' " + name + " ')]";
  }

  instanceMethods.getElementsByClassName = Prototype.BrowserFeatures.XPath ?
  function(element, className) {
    className = className.toString().strip();
    var cond = /\s/.test(className) ? $w(className).map(iter).join('') : iter(className);
    return cond ? document._getElementsByXPath('.//*' + cond, element) : [];
  } : function(element, className) {
    className = className.toString().strip();
    var elements = [], classNames = (/\s/.test(className) ? $w(className) : null);
    if (!classNames && !className) return elements;

    var nodes = $(element).getElementsByTagName('*');
    className = ' ' + className + ' ';

    for (var i = 0, child, cn; child = nodes[i]; i++) {
      if (child.className && (cn = ' ' + child.className + ' ') && (cn.include(className) ||
          (classNames && classNames.all(function(name) {
            return !name.toString().blank() && cn.include(' ' + name + ' ');
          }))))
        elements.push(Element.extend(child));
    }
    return elements;
  };

  return function(className, parentElement) {
    return $(parentElement || document.body).getElementsByClassName(className);
  };
}(Element.Methods);

/*--------------------------------------------------------------------------*/

Element.ClassNames = Class.create();
Element.ClassNames.prototype = {
  initialize: function(element) {
    this.element = $(element);
  },

  _each: function(iterator) {
    this.element.className.split(/\s+/).select(function(name) {
      return name.length > 0;
    })._each(iterator);
  },

  set: function(className) {
    this.element.className = className;
  },

  add: function(classNameToAdd) {
    if (this.include(classNameToAdd)) return;
    this.set($A(this).concat(classNameToAdd).join(' '));
  },

  remove: function(classNameToRemove) {
    if (!this.include(classNameToRemove)) return;
    this.set($A(this).without(classNameToRemove).join(' '));
  },

  toString: function() {
    return $A(this).join(' ');
  }
};

Object.extend(Element.ClassNames.prototype, Enumerable);

/*--------------------------------------------------------------------------*/

(function() {
  window.Selector = Class.create({
    initialize: function(expression) {
      this.expression = expression.strip();
    },

    findElements: function(rootElement) {
      return Prototype.Selector.select(this.expression, rootElement);
    },

    match: function(element) {
      return Prototype.Selector.match(element, this.expression);
    },

    toString: function() {
      return this.expression;
    },

    inspect: function() {
      return "#<Selector: " + this.expression + ">";
    }
  });

  Object.extend(Selector, {
    matchElements: function(elements, expression) {
      var match = Prototype.Selector.match,
          results = [];

      for (var i = 0, length = elements.length; i < length; i++) {
        var element = elements[i];
        if (match(element, expression)) {
          results.push(Element.extend(element));
        }
      }
      return results;
    },

    findElement: function(elements, expression, index) {
      index = index || 0;
      var matchIndex = 0, element;
      for (var i = 0, length = elements.length; i < length; i++) {
        element = elements[i];
        if (Prototype.Selector.match(element, expression) && index === matchIndex++) {
          return Element.extend(element);
        }
      }
    },

    findChildElements: function(element, expressions) {
      var selector = expressions.toArray().join(', ');
      return Prototype.Selector.select(selector, element || document);
    }
  });
})();
/*!
 * jQuery JavaScript Library v1.7.2
 * http://jquery.com/
 *
 * Copyright 2011, John Resig
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * Includes Sizzle.js
 * http://sizzlejs.com/
 * Copyright 2011, The Dojo Foundation
 * Released under the MIT, BSD, and GPL Licenses.
 *
 * Date: Wed Mar 21 12:46:34 2012 -0700
 */
(function( window, undefined ) {

// Use the correct document accordingly with window argument (sandbox)
var document = window.document,
	navigator = window.navigator,
	location = window.location;
var jQuery = (function() {

// Define a local copy of jQuery
var jQuery = function( selector, context ) {
		// The jQuery object is actually just the init constructor 'enhanced'
		return new jQuery.fn.init( selector, context, rootjQuery );
	},

	// Map over jQuery in case of overwrite
	_jQuery = window.jQuery,

	// Map over the $ in case of overwrite
	_$ = window.$,

	// A central reference to the root jQuery(document)
	rootjQuery,

	// A simple way to check for HTML strings or ID strings
	// Prioritize #id over <tag> to avoid XSS via location.hash (#9521)
	quickExpr = /^(?:[^#<]*(<[\w\W]+>)[^>]*$|#([\w\-]*)$)/,

	// Check if a string has a non-whitespace character in it
	rnotwhite = /\S/,

	// Used for trimming whitespace
	trimLeft = /^\s+/,
	trimRight = /\s+$/,

	// Match a standalone tag
	rsingleTag = /^<(\w+)\s*\/?>(?:<\/\1>)?$/,

	// JSON RegExp
	rvalidchars = /^[\],:{}\s]*$/,
	rvalidescape = /\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,
	rvalidtokens = /"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,
	rvalidbraces = /(?:^|:|,)(?:\s*\[)+/g,

	// Useragent RegExp
	rwebkit = /(webkit)[ \/]([\w.]+)/,
	ropera = /(opera)(?:.*version)?[ \/]([\w.]+)/,
	rmsie = /(msie) ([\w.]+)/,
	rmozilla = /(mozilla)(?:.*? rv:([\w.]+))?/,

	// Matches dashed string for camelizing
	rdashAlpha = /-([a-z]|[0-9])/ig,
	rmsPrefix = /^-ms-/,

	// Used by jQuery.camelCase as callback to replace()
	fcamelCase = function( all, letter ) {
		return ( letter + "" ).toUpperCase();
	},

	// Keep a UserAgent string for use with jQuery.browser
	userAgent = navigator.userAgent,

	// For matching the engine and version of the browser
	browserMatch,

	// The deferred used on DOM ready
	readyList,

	// The ready event handler
	DOMContentLoaded,

	// Save a reference to some core methods
	toString = Object.prototype.toString,
	hasOwn = Object.prototype.hasOwnProperty,
	push = Array.prototype.push,
	slice = Array.prototype.slice,
	trim = String.prototype.trim,
	indexOf = Array.prototype.indexOf,

	// [[Class]] -> type pairs
	class2type = {};

jQuery.fn = jQuery.prototype = {
	constructor: jQuery,
	init: function( selector, context, rootjQuery ) {
		var match, elem, ret, doc;

		// Handle $(""), $(null), or $(undefined)
		if ( !selector ) {
			return this;
		}

		// Handle $(DOMElement)
		if ( selector.nodeType ) {
			this.context = this[0] = selector;
			this.length = 1;
			return this;
		}

		// The body element only exists once, optimize finding it
		if ( selector === "body" && !context && document.body ) {
			this.context = document;
			this[0] = document.body;
			this.selector = selector;
			this.length = 1;
			return this;
		}

		// Handle HTML strings
		if ( typeof selector === "string" ) {
			// Are we dealing with HTML string or an ID?
			if ( selector.charAt(0) === "<" && selector.charAt( selector.length - 1 ) === ">" && selector.length >= 3 ) {
				// Assume that strings that start and end with <> are HTML and skip the regex check
				match = [ null, selector, null ];

			} else {
				match = quickExpr.exec( selector );
			}

			// Verify a match, and that no context was specified for #id
			if ( match && (match[1] || !context) ) {

				// HANDLE: $(html) -> $(array)
				if ( match[1] ) {
					context = context instanceof jQuery ? context[0] : context;
					doc = ( context ? context.ownerDocument || context : document );

					// If a single string is passed in and it's a single tag
					// just do a createElement and skip the rest
					ret = rsingleTag.exec( selector );

					if ( ret ) {
						if ( jQuery.isPlainObject( context ) ) {
							selector = [ document.createElement( ret[1] ) ];
							jQuery.fn.attr.call( selector, context, true );

						} else {
							selector = [ doc.createElement( ret[1] ) ];
						}

					} else {
						ret = jQuery.buildFragment( [ match[1] ], [ doc ] );
						selector = ( ret.cacheable ? jQuery.clone(ret.fragment) : ret.fragment ).childNodes;
					}

					return jQuery.merge( this, selector );

				// HANDLE: $("#id")
				} else {
					elem = document.getElementById( match[2] );

					// Check parentNode to catch when Blackberry 4.6 returns
					// nodes that are no longer in the document #6963
					if ( elem && elem.parentNode ) {
						// Handle the case where IE and Opera return items
						// by name instead of ID
						if ( elem.id !== match[2] ) {
							return rootjQuery.find( selector );
						}

						// Otherwise, we inject the element directly into the jQuery object
						this.length = 1;
						this[0] = elem;
					}

					this.context = document;
					this.selector = selector;
					return this;
				}

			// HANDLE: $(expr, $(...))
			} else if ( !context || context.jquery ) {
				return ( context || rootjQuery ).find( selector );

			// HANDLE: $(expr, context)
			// (which is just equivalent to: $(context).find(expr)
			} else {
				return this.constructor( context ).find( selector );
			}

		// HANDLE: $(function)
		// Shortcut for document ready
		} else if ( jQuery.isFunction( selector ) ) {
			return rootjQuery.ready( selector );
		}

		if ( selector.selector !== undefined ) {
			this.selector = selector.selector;
			this.context = selector.context;
		}

		return jQuery.makeArray( selector, this );
	},

	// Start with an empty selector
	selector: "",

	// The current version of jQuery being used
	jquery: "1.7.2",

	// The default length of a jQuery object is 0
	length: 0,

	// The number of elements contained in the matched element set
	size: function() {
		return this.length;
	},

	toArray: function() {
		return slice.call( this, 0 );
	},

	// Get the Nth element in the matched element set OR
	// Get the whole matched element set as a clean array
	get: function( num ) {
		return num == null ?

			// Return a 'clean' array
			this.toArray() :

			// Return just the object
			( num < 0 ? this[ this.length + num ] : this[ num ] );
	},

	// Take an array of elements and push it onto the stack
	// (returning the new matched element set)
	pushStack: function( elems, name, selector ) {
		// Build a new jQuery matched element set
		var ret = this.constructor();

		if ( jQuery.isArray( elems ) ) {
			push.apply( ret, elems );

		} else {
			jQuery.merge( ret, elems );
		}

		// Add the old object onto the stack (as a reference)
		ret.prevObject = this;

		ret.context = this.context;

		if ( name === "find" ) {
			ret.selector = this.selector + ( this.selector ? " " : "" ) + selector;
		} else if ( name ) {
			ret.selector = this.selector + "." + name + "(" + selector + ")";
		}

		// Return the newly-formed element set
		return ret;
	},

	// Execute a callback for every element in the matched set.
	// (You can seed the arguments with an array of args, but this is
	// only used internally.)
	each: function( callback, args ) {
		return jQuery.each( this, callback, args );
	},

	ready: function( fn ) {
		// Attach the listeners
		jQuery.bindReady();

		// Add the callback
		readyList.add( fn );

		return this;
	},

	eq: function( i ) {
		i = +i;
		return i === -1 ?
			this.slice( i ) :
			this.slice( i, i + 1 );
	},

	first: function() {
		return this.eq( 0 );
	},

	last: function() {
		return this.eq( -1 );
	},

	slice: function() {
		return this.pushStack( slice.apply( this, arguments ),
			"slice", slice.call(arguments).join(",") );
	},

	map: function( callback ) {
		return this.pushStack( jQuery.map(this, function( elem, i ) {
			return callback.call( elem, i, elem );
		}));
	},

	end: function() {
		return this.prevObject || this.constructor(null);
	},

	// For internal use only.
	// Behaves like an Array's method, not like a jQuery method.
	push: push,
	sort: [].sort,
	splice: [].splice
};

// Give the init function the jQuery prototype for later instantiation
jQuery.fn.init.prototype = jQuery.fn;

jQuery.extend = jQuery.fn.extend = function() {
	var options, name, src, copy, copyIsArray, clone,
		target = arguments[0] || {},
		i = 1,
		length = arguments.length,
		deep = false;

	// Handle a deep copy situation
	if ( typeof target === "boolean" ) {
		deep = target;
		target = arguments[1] || {};
		// skip the boolean and the target
		i = 2;
	}

	// Handle case when target is a string or something (possible in deep copy)
	if ( typeof target !== "object" && !jQuery.isFunction(target) ) {
		target = {};
	}

	// extend jQuery itself if only one argument is passed
	if ( length === i ) {
		target = this;
		--i;
	}

	for ( ; i < length; i++ ) {
		// Only deal with non-null/undefined values
		if ( (options = arguments[ i ]) != null ) {
			// Extend the base object
			for ( name in options ) {
				src = target[ name ];
				copy = options[ name ];

				// Prevent never-ending loop
				if ( target === copy ) {
					continue;
				}

				// Recurse if we're merging plain objects or arrays
				if ( deep && copy && ( jQuery.isPlainObject(copy) || (copyIsArray = jQuery.isArray(copy)) ) ) {
					if ( copyIsArray ) {
						copyIsArray = false;
						clone = src && jQuery.isArray(src) ? src : [];

					} else {
						clone = src && jQuery.isPlainObject(src) ? src : {};
					}

					// Never move original objects, clone them
					target[ name ] = jQuery.extend( deep, clone, copy );

				// Don't bring in undefined values
				} else if ( copy !== undefined ) {
					target[ name ] = copy;
				}
			}
		}
	}

	// Return the modified object
	return target;
};

jQuery.extend({
	noConflict: function( deep ) {
		if ( window.$ === jQuery ) {
			window.$ = _$;
		}

		if ( deep && window.jQuery === jQuery ) {
			window.jQuery = _jQuery;
		}

		return jQuery;
	},

	// Is the DOM ready to be used? Set to true once it occurs.
	isReady: false,

	// A counter to track how many items to wait for before
	// the ready event fires. See #6781
	readyWait: 1,

	// Hold (or release) the ready event
	holdReady: function( hold ) {
		if ( hold ) {
			jQuery.readyWait++;
		} else {
			jQuery.ready( true );
		}
	},

	// Handle when the DOM is ready
	ready: function( wait ) {
		// Either a released hold or an DOMready/load event and not yet ready
		if ( (wait === true && !--jQuery.readyWait) || (wait !== true && !jQuery.isReady) ) {
			// Make sure body exists, at least, in case IE gets a little overzealous (ticket #5443).
			if ( !document.body ) {
				return setTimeout( jQuery.ready, 1 );
			}

			// Remember that the DOM is ready
			jQuery.isReady = true;

			// If a normal DOM Ready event fired, decrement, and wait if need be
			if ( wait !== true && --jQuery.readyWait > 0 ) {
				return;
			}

			// If there are functions bound, to execute
			readyList.fireWith( document, [ jQuery ] );

			// Trigger any bound ready events
			if ( jQuery.fn.trigger ) {
				jQuery( document ).trigger( "ready" ).off( "ready" );
			}
		}
	},

	bindReady: function() {
		if ( readyList ) {
			return;
		}

		readyList = jQuery.Callbacks( "once memory" );

		// Catch cases where $(document).ready() is called after the
		// browser event has already occurred.
		if ( document.readyState === "complete" ) {
			// Handle it asynchronously to allow scripts the opportunity to delay ready
			return setTimeout( jQuery.ready, 1 );
		}

		// Mozilla, Opera and webkit nightlies currently support this event
		if ( document.addEventListener ) {
			// Use the handy event callback
			document.addEventListener( "DOMContentLoaded", DOMContentLoaded, false );

			// A fallback to window.onload, that will always work
			window.addEventListener( "load", jQuery.ready, false );

		// If IE event model is used
		} else if ( document.attachEvent ) {
			// ensure firing before onload,
			// maybe late but safe also for iframes
			document.attachEvent( "onreadystatechange", DOMContentLoaded );

			// A fallback to window.onload, that will always work
			window.attachEvent( "onload", jQuery.ready );

			// If IE and not a frame
			// continually check to see if the document is ready
			var toplevel = false;

			try {
				toplevel = window.frameElement == null;
			} catch(e) {}

			if ( document.documentElement.doScroll && toplevel ) {
				doScrollCheck();
			}
		}
	},

	// See test/unit/core.js for details concerning isFunction.
	// Since version 1.3, DOM methods and functions like alert
	// aren't supported. They return false on IE (#2968).
	isFunction: function( obj ) {
		return jQuery.type(obj) === "function";
	},

	isArray: Array.isArray || function( obj ) {
		return jQuery.type(obj) === "array";
	},

	isWindow: function( obj ) {
		return obj != null && obj == obj.window;
	},

	isNumeric: function( obj ) {
		return !isNaN( parseFloat(obj) ) && isFinite( obj );
	},

	type: function( obj ) {
		return obj == null ?
			String( obj ) :
			class2type[ toString.call(obj) ] || "object";
	},

	isPlainObject: function( obj ) {
		// Must be an Object.
		// Because of IE, we also have to check the presence of the constructor property.
		// Make sure that DOM nodes and window objects don't pass through, as well
		if ( !obj || jQuery.type(obj) !== "object" || obj.nodeType || jQuery.isWindow( obj ) ) {
			return false;
		}

		try {
			// Not own constructor property must be Object
			if ( obj.constructor &&
				!hasOwn.call(obj, "constructor") &&
				!hasOwn.call(obj.constructor.prototype, "isPrototypeOf") ) {
				return false;
			}
		} catch ( e ) {
			// IE8,9 Will throw exceptions on certain host objects #9897
			return false;
		}

		// Own properties are enumerated firstly, so to speed up,
		// if last one is own, then all properties are own.

		var key;
		for ( key in obj ) {}

		return key === undefined || hasOwn.call( obj, key );
	},

	isEmptyObject: function( obj ) {
		for ( var name in obj ) {
			return false;
		}
		return true;
	},

	error: function( msg ) {
		throw new Error( msg );
	},

	parseJSON: function( data ) {
		if ( typeof data !== "string" || !data ) {
			return null;
		}

		// Make sure leading/trailing whitespace is removed (IE can't handle it)
		data = jQuery.trim( data );

		// Attempt to parse using the native JSON parser first
		if ( window.JSON && window.JSON.parse ) {
			return window.JSON.parse( data );
		}

		// Make sure the incoming data is actual JSON
		// Logic borrowed from http://json.org/json2.js
		if ( rvalidchars.test( data.replace( rvalidescape, "@" )
			.replace( rvalidtokens, "]" )
			.replace( rvalidbraces, "")) ) {

			return ( new Function( "return " + data ) )();

		}
		jQuery.error( "Invalid JSON: " + data );
	},

	// Cross-browser xml parsing
	parseXML: function( data ) {
		if ( typeof data !== "string" || !data ) {
			return null;
		}
		var xml, tmp;
		try {
			if ( window.DOMParser ) { // Standard
				tmp = new DOMParser();
				xml = tmp.parseFromString( data , "text/xml" );
			} else { // IE
				xml = new ActiveXObject( "Microsoft.XMLDOM" );
				xml.async = "false";
				xml.loadXML( data );
			}
		} catch( e ) {
			xml = undefined;
		}
		if ( !xml || !xml.documentElement || xml.getElementsByTagName( "parsererror" ).length ) {
			jQuery.error( "Invalid XML: " + data );
		}
		return xml;
	},

	noop: function() {},

	// Evaluates a script in a global context
	// Workarounds based on findings by Jim Driscoll
	// http://weblogs.java.net/blog/driscoll/archive/2009/09/08/eval-javascript-global-context
	globalEval: function( data ) {
		if ( data && rnotwhite.test( data ) ) {
			// We use execScript on Internet Explorer
			// We use an anonymous function so that context is window
			// rather than jQuery in Firefox
			( window.execScript || function( data ) {
				window[ "eval" ].call( window, data );
			} )( data );
		}
	},

	// Convert dashed to camelCase; used by the css and data modules
	// Microsoft forgot to hump their vendor prefix (#9572)
	camelCase: function( string ) {
		return string.replace( rmsPrefix, "ms-" ).replace( rdashAlpha, fcamelCase );
	},

	nodeName: function( elem, name ) {
		return elem.nodeName && elem.nodeName.toUpperCase() === name.toUpperCase();
	},

	// args is for internal usage only
	each: function( object, callback, args ) {
		var name, i = 0,
			length = object.length,
			isObj = length === undefined || jQuery.isFunction( object );

		if ( args ) {
			if ( isObj ) {
				for ( name in object ) {
					if ( callback.apply( object[ name ], args ) === false ) {
						break;
					}
				}
			} else {
				for ( ; i < length; ) {
					if ( callback.apply( object[ i++ ], args ) === false ) {
						break;
					}
				}
			}

		// A special, fast, case for the most common use of each
		} else {
			if ( isObj ) {
				for ( name in object ) {
					if ( callback.call( object[ name ], name, object[ name ] ) === false ) {
						break;
					}
				}
			} else {
				for ( ; i < length; ) {
					if ( callback.call( object[ i ], i, object[ i++ ] ) === false ) {
						break;
					}
				}
			}
		}

		return object;
	},

	// Use native String.trim function wherever possible
	trim: trim ?
		function( text ) {
			return text == null ?
				"" :
				trim.call( text );
		} :

		// Otherwise use our own trimming functionality
		function( text ) {
			return text == null ?
				"" :
				text.toString().replace( trimLeft, "" ).replace( trimRight, "" );
		},

	// results is for internal usage only
	makeArray: function( array, results ) {
		var ret = results || [];

		if ( array != null ) {
			// The window, strings (and functions) also have 'length'
			// Tweaked logic slightly to handle Blackberry 4.7 RegExp issues #6930
			var type = jQuery.type( array );

			if ( array.length == null || type === "string" || type === "function" || type === "regexp" || jQuery.isWindow( array ) ) {
				push.call( ret, array );
			} else {
				jQuery.merge( ret, array );
			}
		}

		return ret;
	},

	inArray: function( elem, array, i ) {
		var len;

		if ( array ) {
			if ( indexOf ) {
				return indexOf.call( array, elem, i );
			}

			len = array.length;
			i = i ? i < 0 ? Math.max( 0, len + i ) : i : 0;

			for ( ; i < len; i++ ) {
				// Skip accessing in sparse arrays
				if ( i in array && array[ i ] === elem ) {
					return i;
				}
			}
		}

		return -1;
	},

	merge: function( first, second ) {
		var i = first.length,
			j = 0;

		if ( typeof second.length === "number" ) {
			for ( var l = second.length; j < l; j++ ) {
				first[ i++ ] = second[ j ];
			}

		} else {
			while ( second[j] !== undefined ) {
				first[ i++ ] = second[ j++ ];
			}
		}

		first.length = i;

		return first;
	},

	grep: function( elems, callback, inv ) {
		var ret = [], retVal;
		inv = !!inv;

		// Go through the array, only saving the items
		// that pass the validator function
		for ( var i = 0, length = elems.length; i < length; i++ ) {
			retVal = !!callback( elems[ i ], i );
			if ( inv !== retVal ) {
				ret.push( elems[ i ] );
			}
		}

		return ret;
	},

	// arg is for internal usage only
	map: function( elems, callback, arg ) {
		var value, key, ret = [],
			i = 0,
			length = elems.length,
			// jquery objects are treated as arrays
			isArray = elems instanceof jQuery || length !== undefined && typeof length === "number" && ( ( length > 0 && elems[ 0 ] && elems[ length -1 ] ) || length === 0 || jQuery.isArray( elems ) ) ;

		// Go through the array, translating each of the items to their
		if ( isArray ) {
			for ( ; i < length; i++ ) {
				value = callback( elems[ i ], i, arg );

				if ( value != null ) {
					ret[ ret.length ] = value;
				}
			}

		// Go through every key on the object,
		} else {
			for ( key in elems ) {
				value = callback( elems[ key ], key, arg );

				if ( value != null ) {
					ret[ ret.length ] = value;
				}
			}
		}

		// Flatten any nested arrays
		return ret.concat.apply( [], ret );
	},

	// A global GUID counter for objects
	guid: 1,

	// Bind a function to a context, optionally partially applying any
	// arguments.
	proxy: function( fn, context ) {
		if ( typeof context === "string" ) {
			var tmp = fn[ context ];
			context = fn;
			fn = tmp;
		}

		// Quick check to determine if target is callable, in the spec
		// this throws a TypeError, but we will just return undefined.
		if ( !jQuery.isFunction( fn ) ) {
			return undefined;
		}

		// Simulated bind
		var args = slice.call( arguments, 2 ),
			proxy = function() {
				return fn.apply( context, args.concat( slice.call( arguments ) ) );
			};

		// Set the guid of unique handler to the same of original handler, so it can be removed
		proxy.guid = fn.guid = fn.guid || proxy.guid || jQuery.guid++;

		return proxy;
	},

	// Mutifunctional method to get and set values to a collection
	// The value/s can optionally be executed if it's a function
	access: function( elems, fn, key, value, chainable, emptyGet, pass ) {
		var exec,
			bulk = key == null,
			i = 0,
			length = elems.length;

		// Sets many values
		if ( key && typeof key === "object" ) {
			for ( i in key ) {
				jQuery.access( elems, fn, i, key[i], 1, emptyGet, value );
			}
			chainable = 1;

		// Sets one value
		} else if ( value !== undefined ) {
			// Optionally, function values get executed if exec is true
			exec = pass === undefined && jQuery.isFunction( value );

			if ( bulk ) {
				// Bulk operations only iterate when executing function values
				if ( exec ) {
					exec = fn;
					fn = function( elem, key, value ) {
						return exec.call( jQuery( elem ), value );
					};

				// Otherwise they run against the entire set
				} else {
					fn.call( elems, value );
					fn = null;
				}
			}

			if ( fn ) {
				for (; i < length; i++ ) {
					fn( elems[i], key, exec ? value.call( elems[i], i, fn( elems[i], key ) ) : value, pass );
				}
			}

			chainable = 1;
		}

		return chainable ?
			elems :

			// Gets
			bulk ?
				fn.call( elems ) :
				length ? fn( elems[0], key ) : emptyGet;
	},

	now: function() {
		return ( new Date() ).getTime();
	},

	// Use of jQuery.browser is frowned upon.
	// More details: http://docs.jquery.com/Utilities/jQuery.browser
	uaMatch: function( ua ) {
		ua = ua.toLowerCase();

		var match = rwebkit.exec( ua ) ||
			ropera.exec( ua ) ||
			rmsie.exec( ua ) ||
			ua.indexOf("compatible") < 0 && rmozilla.exec( ua ) ||
			[];

		return { browser: match[1] || "", version: match[2] || "0" };
	},

	sub: function() {
		function jQuerySub( selector, context ) {
			return new jQuerySub.fn.init( selector, context );
		}
		jQuery.extend( true, jQuerySub, this );
		jQuerySub.superclass = this;
		jQuerySub.fn = jQuerySub.prototype = this();
		jQuerySub.fn.constructor = jQuerySub;
		jQuerySub.sub = this.sub;
		jQuerySub.fn.init = function init( selector, context ) {
			if ( context && context instanceof jQuery && !(context instanceof jQuerySub) ) {
				context = jQuerySub( context );
			}

			return jQuery.fn.init.call( this, selector, context, rootjQuerySub );
		};
		jQuerySub.fn.init.prototype = jQuerySub.fn;
		var rootjQuerySub = jQuerySub(document);
		return jQuerySub;
	},

	browser: {}
});

// Populate the class2type map
jQuery.each("Boolean Number String Function Array Date RegExp Object".split(" "), function(i, name) {
	class2type[ "[object " + name + "]" ] = name.toLowerCase();
});

browserMatch = jQuery.uaMatch( userAgent );
if ( browserMatch.browser ) {
	jQuery.browser[ browserMatch.browser ] = true;
	jQuery.browser.version = browserMatch.version;
}

// Deprecated, use jQuery.browser.webkit instead
if ( jQuery.browser.webkit ) {
	jQuery.browser.safari = true;
}

// IE doesn't match non-breaking spaces with \s
if ( rnotwhite.test( "\xA0" ) ) {
	trimLeft = /^[\s\xA0]+/;
	trimRight = /[\s\xA0]+$/;
}

// All jQuery objects should point back to these
rootjQuery = jQuery(document);

// Cleanup functions for the document ready method
if ( document.addEventListener ) {
	DOMContentLoaded = function() {
		document.removeEventListener( "DOMContentLoaded", DOMContentLoaded, false );
		jQuery.ready();
	};

} else if ( document.attachEvent ) {
	DOMContentLoaded = function() {
		// Make sure body exists, at least, in case IE gets a little overzealous (ticket #5443).
		if ( document.readyState === "complete" ) {
			document.detachEvent( "onreadystatechange", DOMContentLoaded );
			jQuery.ready();
		}
	};
}

// The DOM ready check for Internet Explorer
function doScrollCheck() {
	if ( jQuery.isReady ) {
		return;
	}

	try {
		// If IE is used, use the trick by Diego Perini
		// http://javascript.nwbox.com/IEContentLoaded/
		document.documentElement.doScroll("left");
	} catch(e) {
		setTimeout( doScrollCheck, 1 );
		return;
	}

	// and execute any waiting functions
	jQuery.ready();
}

return jQuery;

})();


// String to Object flags format cache
var flagsCache = {};

// Convert String-formatted flags into Object-formatted ones and store in cache
function createFlags( flags ) {
	var object = flagsCache[ flags ] = {},
		i, length;
	flags = flags.split( /\s+/ );
	for ( i = 0, length = flags.length; i < length; i++ ) {
		object[ flags[i] ] = true;
	}
	return object;
}

/*
 * Create a callback list using the following parameters:
 *
 *	flags:	an optional list of space-separated flags that will change how
 *			the callback list behaves
 *
 * By default a callback list will act like an event callback list and can be
 * "fired" multiple times.
 *
 * Possible flags:
 *
 *	once:			will ensure the callback list can only be fired once (like a Deferred)
 *
 *	memory:			will keep track of previous values and will call any callback added
 *					after the list has been fired right away with the latest "memorized"
 *					values (like a Deferred)
 *
 *	unique:			will ensure a callback can only be added once (no duplicate in the list)
 *
 *	stopOnFalse:	interrupt callings when a callback returns false
 *
 */
jQuery.Callbacks = function( flags ) {

	// Convert flags from String-formatted to Object-formatted
	// (we check in cache first)
	flags = flags ? ( flagsCache[ flags ] || createFlags( flags ) ) : {};

	var // Actual callback list
		list = [],
		// Stack of fire calls for repeatable lists
		stack = [],
		// Last fire value (for non-forgettable lists)
		memory,
		// Flag to know if list was already fired
		fired,
		// Flag to know if list is currently firing
		firing,
		// First callback to fire (used internally by add and fireWith)
		firingStart,
		// End of the loop when firing
		firingLength,
		// Index of currently firing callback (modified by remove if needed)
		firingIndex,
		// Add one or several callbacks to the list
		add = function( args ) {
			var i,
				length,
				elem,
				type,
				actual;
			for ( i = 0, length = args.length; i < length; i++ ) {
				elem = args[ i ];
				type = jQuery.type( elem );
				if ( type === "array" ) {
					// Inspect recursively
					add( elem );
				} else if ( type === "function" ) {
					// Add if not in unique mode and callback is not in
					if ( !flags.unique || !self.has( elem ) ) {
						list.push( elem );
					}
				}
			}
		},
		// Fire callbacks
		fire = function( context, args ) {
			args = args || [];
			memory = !flags.memory || [ context, args ];
			fired = true;
			firing = true;
			firingIndex = firingStart || 0;
			firingStart = 0;
			firingLength = list.length;
			for ( ; list && firingIndex < firingLength; firingIndex++ ) {
				if ( list[ firingIndex ].apply( context, args ) === false && flags.stopOnFalse ) {
					memory = true; // Mark as halted
					break;
				}
			}
			firing = false;
			if ( list ) {
				if ( !flags.once ) {
					if ( stack && stack.length ) {
						memory = stack.shift();
						self.fireWith( memory[ 0 ], memory[ 1 ] );
					}
				} else if ( memory === true ) {
					self.disable();
				} else {
					list = [];
				}
			}
		},
		// Actual Callbacks object
		self = {
			// Add a callback or a collection of callbacks to the list
			add: function() {
				if ( list ) {
					var length = list.length;
					add( arguments );
					// Do we need to add the callbacks to the
					// current firing batch?
					if ( firing ) {
						firingLength = list.length;
					// With memory, if we're not firing then
					// we should call right away, unless previous
					// firing was halted (stopOnFalse)
					} else if ( memory && memory !== true ) {
						firingStart = length;
						fire( memory[ 0 ], memory[ 1 ] );
					}
				}
				return this;
			},
			// Remove a callback from the list
			remove: function() {
				if ( list ) {
					var args = arguments,
						argIndex = 0,
						argLength = args.length;
					for ( ; argIndex < argLength ; argIndex++ ) {
						for ( var i = 0; i < list.length; i++ ) {
							if ( args[ argIndex ] === list[ i ] ) {
								// Handle firingIndex and firingLength
								if ( firing ) {
									if ( i <= firingLength ) {
										firingLength--;
										if ( i <= firingIndex ) {
											firingIndex--;
										}
									}
								}
								// Remove the element
								list.splice( i--, 1 );
								// If we have some unicity property then
								// we only need to do this once
								if ( flags.unique ) {
									break;
								}
							}
						}
					}
				}
				return this;
			},
			// Control if a given callback is in the list
			has: function( fn ) {
				if ( list ) {
					var i = 0,
						length = list.length;
					for ( ; i < length; i++ ) {
						if ( fn === list[ i ] ) {
							return true;
						}
					}
				}
				return false;
			},
			// Remove all callbacks from the list
			empty: function() {
				list = [];
				return this;
			},
			// Have the list do nothing anymore
			disable: function() {
				list = stack = memory = undefined;
				return this;
			},
			// Is it disabled?
			disabled: function() {
				return !list;
			},
			// Lock the list in its current state
			lock: function() {
				stack = undefined;
				if ( !memory || memory === true ) {
					self.disable();
				}
				return this;
			},
			// Is it locked?
			locked: function() {
				return !stack;
			},
			// Call all callbacks with the given context and arguments
			fireWith: function( context, args ) {
				if ( stack ) {
					if ( firing ) {
						if ( !flags.once ) {
							stack.push( [ context, args ] );
						}
					} else if ( !( flags.once && memory ) ) {
						fire( context, args );
					}
				}
				return this;
			},
			// Call all the callbacks with the given arguments
			fire: function() {
				self.fireWith( this, arguments );
				return this;
			},
			// To know if the callbacks have already been called at least once
			fired: function() {
				return !!fired;
			}
		};

	return self;
};




var // Static reference to slice
	sliceDeferred = [].slice;

jQuery.extend({

	Deferred: function( func ) {
		var doneList = jQuery.Callbacks( "once memory" ),
			failList = jQuery.Callbacks( "once memory" ),
			progressList = jQuery.Callbacks( "memory" ),
			state = "pending",
			lists = {
				resolve: doneList,
				reject: failList,
				notify: progressList
			},
			promise = {
				done: doneList.add,
				fail: failList.add,
				progress: progressList.add,

				state: function() {
					return state;
				},

				// Deprecated
				isResolved: doneList.fired,
				isRejected: failList.fired,

				then: function( doneCallbacks, failCallbacks, progressCallbacks ) {
					deferred.done( doneCallbacks ).fail( failCallbacks ).progress( progressCallbacks );
					return this;
				},
				always: function() {
					deferred.done.apply( deferred, arguments ).fail.apply( deferred, arguments );
					return this;
				},
				pipe: function( fnDone, fnFail, fnProgress ) {
					return jQuery.Deferred(function( newDefer ) {
						jQuery.each( {
							done: [ fnDone, "resolve" ],
							fail: [ fnFail, "reject" ],
							progress: [ fnProgress, "notify" ]
						}, function( handler, data ) {
							var fn = data[ 0 ],
								action = data[ 1 ],
								returned;
							if ( jQuery.isFunction( fn ) ) {
								deferred[ handler ](function() {
									returned = fn.apply( this, arguments );
									if ( returned && jQuery.isFunction( returned.promise ) ) {
										returned.promise().then( newDefer.resolve, newDefer.reject, newDefer.notify );
									} else {
										newDefer[ action + "With" ]( this === deferred ? newDefer : this, [ returned ] );
									}
								});
							} else {
								deferred[ handler ]( newDefer[ action ] );
							}
						});
					}).promise();
				},
				// Get a promise for this deferred
				// If obj is provided, the promise aspect is added to the object
				promise: function( obj ) {
					if ( obj == null ) {
						obj = promise;
					} else {
						for ( var key in promise ) {
							obj[ key ] = promise[ key ];
						}
					}
					return obj;
				}
			},
			deferred = promise.promise({}),
			key;

		for ( key in lists ) {
			deferred[ key ] = lists[ key ].fire;
			deferred[ key + "With" ] = lists[ key ].fireWith;
		}

		// Handle state
		deferred.done( function() {
			state = "resolved";
		}, failList.disable, progressList.lock ).fail( function() {
			state = "rejected";
		}, doneList.disable, progressList.lock );

		// Call given func if any
		if ( func ) {
			func.call( deferred, deferred );
		}

		// All done!
		return deferred;
	},

	// Deferred helper
	when: function( firstParam ) {
		var args = sliceDeferred.call( arguments, 0 ),
			i = 0,
			length = args.length,
			pValues = new Array( length ),
			count = length,
			pCount = length,
			deferred = length <= 1 && firstParam && jQuery.isFunction( firstParam.promise ) ?
				firstParam :
				jQuery.Deferred(),
			promise = deferred.promise();
		function resolveFunc( i ) {
			return function( value ) {
				args[ i ] = arguments.length > 1 ? sliceDeferred.call( arguments, 0 ) : value;
				if ( !( --count ) ) {
					deferred.resolveWith( deferred, args );
				}
			};
		}
		function progressFunc( i ) {
			return function( value ) {
				pValues[ i ] = arguments.length > 1 ? sliceDeferred.call( arguments, 0 ) : value;
				deferred.notifyWith( promise, pValues );
			};
		}
		if ( length > 1 ) {
			for ( ; i < length; i++ ) {
				if ( args[ i ] && args[ i ].promise && jQuery.isFunction( args[ i ].promise ) ) {
					args[ i ].promise().then( resolveFunc(i), deferred.reject, progressFunc(i) );
				} else {
					--count;
				}
			}
			if ( !count ) {
				deferred.resolveWith( deferred, args );
			}
		} else if ( deferred !== firstParam ) {
			deferred.resolveWith( deferred, length ? [ firstParam ] : [] );
		}
		return promise;
	}
});




jQuery.support = (function() {

	var support,
		all,
		a,
		select,
		opt,
		input,
		fragment,
		tds,
		events,
		eventName,
		i,
		isSupported,
		div = document.createElement( "div" ),
		documentElement = document.documentElement;

	// Preliminary tests
	div.setAttribute("className", "t");
	div.innerHTML = "   <link/><table></table><a href='/a' style='top:1px;float:left;opacity:.55;'>a</a><input type='checkbox'/>";

	all = div.getElementsByTagName( "*" );
	a = div.getElementsByTagName( "a" )[ 0 ];

	// Can't get basic test support
	if ( !all || !all.length || !a ) {
		return {};
	}

	// First batch of supports tests
	select = document.createElement( "select" );
	opt = select.appendChild( document.createElement("option") );
	input = div.getElementsByTagName( "input" )[ 0 ];

	support = {
		// IE strips leading whitespace when .innerHTML is used
		leadingWhitespace: ( div.firstChild.nodeType === 3 ),

		// Make sure that tbody elements aren't automatically inserted
		// IE will insert them into empty tables
		tbody: !div.getElementsByTagName("tbody").length,

		// Make sure that link elements get serialized correctly by innerHTML
		// This requires a wrapper element in IE
		htmlSerialize: !!div.getElementsByTagName("link").length,

		// Get the style information from getAttribute
		// (IE uses .cssText instead)
		style: /top/.test( a.getAttribute("style") ),

		// Make sure that URLs aren't manipulated
		// (IE normalizes it by default)
		hrefNormalized: ( a.getAttribute("href") === "/a" ),

		// Make sure that element opacity exists
		// (IE uses filter instead)
		// Use a regex to work around a WebKit issue. See #5145
		opacity: /^0.55/.test( a.style.opacity ),

		// Verify style float existence
		// (IE uses styleFloat instead of cssFloat)
		cssFloat: !!a.style.cssFloat,

		// Make sure that if no value is specified for a checkbox
		// that it defaults to "on".
		// (WebKit defaults to "" instead)
		checkOn: ( input.value === "on" ),

		// Make sure that a selected-by-default option has a working selected property.
		// (WebKit defaults to false instead of true, IE too, if it's in an optgroup)
		optSelected: opt.selected,

		// Test setAttribute on camelCase class. If it works, we need attrFixes when doing get/setAttribute (ie6/7)
		getSetAttribute: div.className !== "t",

		// Tests for enctype support on a form(#6743)
		enctype: !!document.createElement("form").enctype,

		// Makes sure cloning an html5 element does not cause problems
		// Where outerHTML is undefined, this still works
		html5Clone: document.createElement("nav").cloneNode( true ).outerHTML !== "<:nav></:nav>",

		// Will be defined later
		submitBubbles: true,
		changeBubbles: true,
		focusinBubbles: false,
		deleteExpando: true,
		noCloneEvent: true,
		inlineBlockNeedsLayout: false,
		shrinkWrapBlocks: false,
		reliableMarginRight: true,
		pixelMargin: true
	};

	// jQuery.boxModel DEPRECATED in 1.3, use jQuery.support.boxModel instead
	jQuery.boxModel = support.boxModel = (document.compatMode === "CSS1Compat");

	// Make sure checked status is properly cloned
	input.checked = true;
	support.noCloneChecked = input.cloneNode( true ).checked;

	// Make sure that the options inside disabled selects aren't marked as disabled
	// (WebKit marks them as disabled)
	select.disabled = true;
	support.optDisabled = !opt.disabled;

	// Test to see if it's possible to delete an expando from an element
	// Fails in Internet Explorer
	try {
		delete div.test;
	} catch( e ) {
		support.deleteExpando = false;
	}

	if ( !div.addEventListener && div.attachEvent && div.fireEvent ) {
		div.attachEvent( "onclick", function() {
			// Cloning a node shouldn't copy over any
			// bound event handlers (IE does this)
			support.noCloneEvent = false;
		});
		div.cloneNode( true ).fireEvent( "onclick" );
	}

	// Check if a radio maintains its value
	// after being appended to the DOM
	input = document.createElement("input");
	input.value = "t";
	input.setAttribute("type", "radio");
	support.radioValue = input.value === "t";

	input.setAttribute("checked", "checked");

	// #11217 - WebKit loses check when the name is after the checked attribute
	input.setAttribute( "name", "t" );

	div.appendChild( input );
	fragment = document.createDocumentFragment();
	fragment.appendChild( div.lastChild );

	// WebKit doesn't clone checked state correctly in fragments
	support.checkClone = fragment.cloneNode( true ).cloneNode( true ).lastChild.checked;

	// Check if a disconnected checkbox will retain its checked
	// value of true after appended to the DOM (IE6/7)
	support.appendChecked = input.checked;

	fragment.removeChild( input );
	fragment.appendChild( div );

	// Technique from Juriy Zaytsev
	// http://perfectionkills.com/detecting-event-support-without-browser-sniffing/
	// We only care about the case where non-standard event systems
	// are used, namely in IE. Short-circuiting here helps us to
	// avoid an eval call (in setAttribute) which can cause CSP
	// to go haywire. See: https://developer.mozilla.org/en/Security/CSP
	if ( div.attachEvent ) {
		for ( i in {
			submit: 1,
			change: 1,
			focusin: 1
		}) {
			eventName = "on" + i;
			isSupported = ( eventName in div );
			if ( !isSupported ) {
				div.setAttribute( eventName, "return;" );
				isSupported = ( typeof div[ eventName ] === "function" );
			}
			support[ i + "Bubbles" ] = isSupported;
		}
	}

	fragment.removeChild( div );

	// Null elements to avoid leaks in IE
	fragment = select = opt = div = input = null;

	// Run tests that need a body at doc ready
	jQuery(function() {
		var container, outer, inner, table, td, offsetSupport,
			marginDiv, conMarginTop, style, html, positionTopLeftWidthHeight,
			paddingMarginBorderVisibility, paddingMarginBorder,
			body = document.getElementsByTagName("body")[0];

		if ( !body ) {
			// Return for frameset docs that don't have a body
			return;
		}

		conMarginTop = 1;
		paddingMarginBorder = "padding:0;margin:0;border:";
		positionTopLeftWidthHeight = "position:absolute;top:0;left:0;width:1px;height:1px;";
		paddingMarginBorderVisibility = paddingMarginBorder + "0;visibility:hidden;";
		style = "style='" + positionTopLeftWidthHeight + paddingMarginBorder + "5px solid #000;";
		html = "<div " + style + "display:block;'><div style='" + paddingMarginBorder + "0;display:block;overflow:hidden;'></div></div>" +
			"<table " + style + "' cellpadding='0' cellspacing='0'>" +
			"<tr><td></td></tr></table>";

		container = document.createElement("div");
		container.style.cssText = paddingMarginBorderVisibility + "width:0;height:0;position:static;top:0;margin-top:" + conMarginTop + "px";
		body.insertBefore( container, body.firstChild );

		// Construct the test element
		div = document.createElement("div");
		container.appendChild( div );

		// Check if table cells still have offsetWidth/Height when they are set
		// to display:none and there are still other visible table cells in a
		// table row; if so, offsetWidth/Height are not reliable for use when
		// determining if an element has been hidden directly using
		// display:none (it is still safe to use offsets if a parent element is
		// hidden; don safety goggles and see bug #4512 for more information).
		// (only IE 8 fails this test)
		div.innerHTML = "<table><tr><td style='" + paddingMarginBorder + "0;display:none'></td><td>t</td></tr></table>";
		tds = div.getElementsByTagName( "td" );
		isSupported = ( tds[ 0 ].offsetHeight === 0 );

		tds[ 0 ].style.display = "";
		tds[ 1 ].style.display = "none";

		// Check if empty table cells still have offsetWidth/Height
		// (IE <= 8 fail this test)
		support.reliableHiddenOffsets = isSupported && ( tds[ 0 ].offsetHeight === 0 );

		// Check if div with explicit width and no margin-right incorrectly
		// gets computed margin-right based on width of container. For more
		// info see bug #3333
		// Fails in WebKit before Feb 2011 nightlies
		// WebKit Bug 13343 - getComputedStyle returns wrong value for margin-right
		if ( window.getComputedStyle ) {
			div.innerHTML = "";
			marginDiv = document.createElement( "div" );
			marginDiv.style.width = "0";
			marginDiv.style.marginRight = "0";
			div.style.width = "2px";
			div.appendChild( marginDiv );
			support.reliableMarginRight =
				( parseInt( ( window.getComputedStyle( marginDiv, null ) || { marginRight: 0 } ).marginRight, 10 ) || 0 ) === 0;
		}

		if ( typeof div.style.zoom !== "undefined" ) {
			// Check if natively block-level elements act like inline-block
			// elements when setting their display to 'inline' and giving
			// them layout
			// (IE < 8 does this)
			div.innerHTML = "";
			div.style.width = div.style.padding = "1px";
			div.style.border = 0;
			div.style.overflow = "hidden";
			div.style.display = "inline";
			div.style.zoom = 1;
			support.inlineBlockNeedsLayout = ( div.offsetWidth === 3 );

			// Check if elements with layout shrink-wrap their children
			// (IE 6 does this)
			div.style.display = "block";
			div.style.overflow = "visible";
			div.innerHTML = "<div style='width:5px;'></div>";
			support.shrinkWrapBlocks = ( div.offsetWidth !== 3 );
		}

		div.style.cssText = positionTopLeftWidthHeight + paddingMarginBorderVisibility;
		div.innerHTML = html;

		outer = div.firstChild;
		inner = outer.firstChild;
		td = outer.nextSibling.firstChild.firstChild;

		offsetSupport = {
			doesNotAddBorder: ( inner.offsetTop !== 5 ),
			doesAddBorderForTableAndCells: ( td.offsetTop === 5 )
		};

		inner.style.position = "fixed";
		inner.style.top = "20px";

		// safari subtracts parent border width here which is 5px
		offsetSupport.fixedPosition = ( inner.offsetTop === 20 || inner.offsetTop === 15 );
		inner.style.position = inner.style.top = "";

		outer.style.overflow = "hidden";
		outer.style.position = "relative";

		offsetSupport.subtractsBorderForOverflowNotVisible = ( inner.offsetTop === -5 );
		offsetSupport.doesNotIncludeMarginInBodyOffset = ( body.offsetTop !== conMarginTop );

		if ( window.getComputedStyle ) {
			div.style.marginTop = "1%";
			support.pixelMargin = ( window.getComputedStyle( div, null ) || { marginTop: 0 } ).marginTop !== "1%";
		}

		if ( typeof container.style.zoom !== "undefined" ) {
			container.style.zoom = 1;
		}

		body.removeChild( container );
		marginDiv = div = container = null;

		jQuery.extend( support, offsetSupport );
	});

	return support;
})();




var rbrace = /^(?:\{.*\}|\[.*\])$/,
	rmultiDash = /([A-Z])/g;

jQuery.extend({
	cache: {},

	// Please use with caution
	uuid: 0,

	// Unique for each copy of jQuery on the page
	// Non-digits removed to match rinlinejQuery
	expando: "jQuery" + ( jQuery.fn.jquery + Math.random() ).replace( /\D/g, "" ),

	// The following elements throw uncatchable exceptions if you
	// attempt to add expando properties to them.
	noData: {
		"embed": true,
		// Ban all objects except for Flash (which handle expandos)
		"object": "clsid:D27CDB6E-AE6D-11cf-96B8-444553540000",
		"applet": true
	},

	hasData: function( elem ) {
		elem = elem.nodeType ? jQuery.cache[ elem[jQuery.expando] ] : elem[ jQuery.expando ];
		return !!elem && !isEmptyDataObject( elem );
	},

	data: function( elem, name, data, pvt /* Internal Use Only */ ) {
		if ( !jQuery.acceptData( elem ) ) {
			return;
		}

		var privateCache, thisCache, ret,
			internalKey = jQuery.expando,
			getByName = typeof name === "string",

			// We have to handle DOM nodes and JS objects differently because IE6-7
			// can't GC object references properly across the DOM-JS boundary
			isNode = elem.nodeType,

			// Only DOM nodes need the global jQuery cache; JS object data is
			// attached directly to the object so GC can occur automatically
			cache = isNode ? jQuery.cache : elem,

			// Only defining an ID for JS objects if its cache already exists allows
			// the code to shortcut on the same path as a DOM node with no cache
			id = isNode ? elem[ internalKey ] : elem[ internalKey ] && internalKey,
			isEvents = name === "events";

		// Avoid doing any more work than we need to when trying to get data on an
		// object that has no data at all
		if ( (!id || !cache[id] || (!isEvents && !pvt && !cache[id].data)) && getByName && data === undefined ) {
			return;
		}

		if ( !id ) {
			// Only DOM nodes need a new unique ID for each element since their data
			// ends up in the global cache
			if ( isNode ) {
				elem[ internalKey ] = id = ++jQuery.uuid;
			} else {
				id = internalKey;
			}
		}

		if ( !cache[ id ] ) {
			cache[ id ] = {};

			// Avoids exposing jQuery metadata on plain JS objects when the object
			// is serialized using JSON.stringify
			if ( !isNode ) {
				cache[ id ].toJSON = jQuery.noop;
			}
		}

		// An object can be passed to jQuery.data instead of a key/value pair; this gets
		// shallow copied over onto the existing cache
		if ( typeof name === "object" || typeof name === "function" ) {
			if ( pvt ) {
				cache[ id ] = jQuery.extend( cache[ id ], name );
			} else {
				cache[ id ].data = jQuery.extend( cache[ id ].data, name );
			}
		}

		privateCache = thisCache = cache[ id ];

		// jQuery data() is stored in a separate object inside the object's internal data
		// cache in order to avoid key collisions between internal data and user-defined
		// data.
		if ( !pvt ) {
			if ( !thisCache.data ) {
				thisCache.data = {};
			}

			thisCache = thisCache.data;
		}

		if ( data !== undefined ) {
			thisCache[ jQuery.camelCase( name ) ] = data;
		}

		// Users should not attempt to inspect the internal events object using jQuery.data,
		// it is undocumented and subject to change. But does anyone listen? No.
		if ( isEvents && !thisCache[ name ] ) {
			return privateCache.events;
		}

		// Check for both converted-to-camel and non-converted data property names
		// If a data property was specified
		if ( getByName ) {

			// First Try to find as-is property data
			ret = thisCache[ name ];

			// Test for null|undefined property data
			if ( ret == null ) {

				// Try to find the camelCased property
				ret = thisCache[ jQuery.camelCase( name ) ];
			}
		} else {
			ret = thisCache;
		}

		return ret;
	},

	removeData: function( elem, name, pvt /* Internal Use Only */ ) {
		if ( !jQuery.acceptData( elem ) ) {
			return;
		}

		var thisCache, i, l,

			// Reference to internal data cache key
			internalKey = jQuery.expando,

			isNode = elem.nodeType,

			// See jQuery.data for more information
			cache = isNode ? jQuery.cache : elem,

			// See jQuery.data for more information
			id = isNode ? elem[ internalKey ] : internalKey;

		// If there is already no cache entry for this object, there is no
		// purpose in continuing
		if ( !cache[ id ] ) {
			return;
		}

		if ( name ) {

			thisCache = pvt ? cache[ id ] : cache[ id ].data;

			if ( thisCache ) {

				// Support array or space separated string names for data keys
				if ( !jQuery.isArray( name ) ) {

					// try the string as a key before any manipulation
					if ( name in thisCache ) {
						name = [ name ];
					} else {

						// split the camel cased version by spaces unless a key with the spaces exists
						name = jQuery.camelCase( name );
						if ( name in thisCache ) {
							name = [ name ];
						} else {
							name = name.split( " " );
						}
					}
				}

				for ( i = 0, l = name.length; i < l; i++ ) {
					delete thisCache[ name[i] ];
				}

				// If there is no data left in the cache, we want to continue
				// and let the cache object itself get destroyed
				if ( !( pvt ? isEmptyDataObject : jQuery.isEmptyObject )( thisCache ) ) {
					return;
				}
			}
		}

		// See jQuery.data for more information
		if ( !pvt ) {
			delete cache[ id ].data;

			// Don't destroy the parent cache unless the internal data object
			// had been the only thing left in it
			if ( !isEmptyDataObject(cache[ id ]) ) {
				return;
			}
		}

		// Browsers that fail expando deletion also refuse to delete expandos on
		// the window, but it will allow it on all other JS objects; other browsers
		// don't care
		// Ensure that `cache` is not a window object #10080
		if ( jQuery.support.deleteExpando || !cache.setInterval ) {
			delete cache[ id ];
		} else {
			cache[ id ] = null;
		}

		// We destroyed the cache and need to eliminate the expando on the node to avoid
		// false lookups in the cache for entries that no longer exist
		if ( isNode ) {
			// IE does not allow us to delete expando properties from nodes,
			// nor does it have a removeAttribute function on Document nodes;
			// we must handle all of these cases
			if ( jQuery.support.deleteExpando ) {
				delete elem[ internalKey ];
			} else if ( elem.removeAttribute ) {
				elem.removeAttribute( internalKey );
			} else {
				elem[ internalKey ] = null;
			}
		}
	},

	// For internal use only.
	_data: function( elem, name, data ) {
		return jQuery.data( elem, name, data, true );
	},

	// A method for determining if a DOM node can handle the data expando
	acceptData: function( elem ) {
		if ( elem.nodeName ) {
			var match = jQuery.noData[ elem.nodeName.toLowerCase() ];

			if ( match ) {
				return !(match === true || elem.getAttribute("classid") !== match);
			}
		}

		return true;
	}
});

jQuery.fn.extend({
	data: function( key, value ) {
		var parts, part, attr, name, l,
			elem = this[0],
			i = 0,
			data = null;

		// Gets all values
		if ( key === undefined ) {
			if ( this.length ) {
				data = jQuery.data( elem );

				if ( elem.nodeType === 1 && !jQuery._data( elem, "parsedAttrs" ) ) {
					attr = elem.attributes;
					for ( l = attr.length; i < l; i++ ) {
						name = attr[i].name;

						if ( name.indexOf( "data-" ) === 0 ) {
							name = jQuery.camelCase( name.substring(5) );

							dataAttr( elem, name, data[ name ] );
						}
					}
					jQuery._data( elem, "parsedAttrs", true );
				}
			}

			return data;
		}

		// Sets multiple values
		if ( typeof key === "object" ) {
			return this.each(function() {
				jQuery.data( this, key );
			});
		}

		parts = key.split( ".", 2 );
		parts[1] = parts[1] ? "." + parts[1] : "";
		part = parts[1] + "!";

		return jQuery.access( this, function( value ) {

			if ( value === undefined ) {
				data = this.triggerHandler( "getData" + part, [ parts[0] ] );

				// Try to fetch any internally stored data first
				if ( data === undefined && elem ) {
					data = jQuery.data( elem, key );
					data = dataAttr( elem, key, data );
				}

				return data === undefined && parts[1] ?
					this.data( parts[0] ) :
					data;
			}

			parts[1] = value;
			this.each(function() {
				var self = jQuery( this );

				self.triggerHandler( "setData" + part, parts );
				jQuery.data( this, key, value );
				self.triggerHandler( "changeData" + part, parts );
			});
		}, null, value, arguments.length > 1, null, false );
	},

	removeData: function( key ) {
		return this.each(function() {
			jQuery.removeData( this, key );
		});
	}
});

function dataAttr( elem, key, data ) {
	// If nothing was found internally, try to fetch any
	// data from the HTML5 data-* attribute
	if ( data === undefined && elem.nodeType === 1 ) {

		var name = "data-" + key.replace( rmultiDash, "-$1" ).toLowerCase();

		data = elem.getAttribute( name );

		if ( typeof data === "string" ) {
			try {
				data = data === "true" ? true :
				data === "false" ? false :
				data === "null" ? null :
				jQuery.isNumeric( data ) ? +data :
					rbrace.test( data ) ? jQuery.parseJSON( data ) :
					data;
			} catch( e ) {}

			// Make sure we set the data so it isn't changed later
			jQuery.data( elem, key, data );

		} else {
			data = undefined;
		}
	}

	return data;
}

// checks a cache object for emptiness
function isEmptyDataObject( obj ) {
	for ( var name in obj ) {

		// if the public data object is empty, the private is still empty
		if ( name === "data" && jQuery.isEmptyObject( obj[name] ) ) {
			continue;
		}
		if ( name !== "toJSON" ) {
			return false;
		}
	}

	return true;
}




function handleQueueMarkDefer( elem, type, src ) {
	var deferDataKey = type + "defer",
		queueDataKey = type + "queue",
		markDataKey = type + "mark",
		defer = jQuery._data( elem, deferDataKey );
	if ( defer &&
		( src === "queue" || !jQuery._data(elem, queueDataKey) ) &&
		( src === "mark" || !jQuery._data(elem, markDataKey) ) ) {
		// Give room for hard-coded callbacks to fire first
		// and eventually mark/queue something else on the element
		setTimeout( function() {
			if ( !jQuery._data( elem, queueDataKey ) &&
				!jQuery._data( elem, markDataKey ) ) {
				jQuery.removeData( elem, deferDataKey, true );
				defer.fire();
			}
		}, 0 );
	}
}

jQuery.extend({

	_mark: function( elem, type ) {
		if ( elem ) {
			type = ( type || "fx" ) + "mark";
			jQuery._data( elem, type, (jQuery._data( elem, type ) || 0) + 1 );
		}
	},

	_unmark: function( force, elem, type ) {
		if ( force !== true ) {
			type = elem;
			elem = force;
			force = false;
		}
		if ( elem ) {
			type = type || "fx";
			var key = type + "mark",
				count = force ? 0 : ( (jQuery._data( elem, key ) || 1) - 1 );
			if ( count ) {
				jQuery._data( elem, key, count );
			} else {
				jQuery.removeData( elem, key, true );
				handleQueueMarkDefer( elem, type, "mark" );
			}
		}
	},

	queue: function( elem, type, data ) {
		var q;
		if ( elem ) {
			type = ( type || "fx" ) + "queue";
			q = jQuery._data( elem, type );

			// Speed up dequeue by getting out quickly if this is just a lookup
			if ( data ) {
				if ( !q || jQuery.isArray(data) ) {
					q = jQuery._data( elem, type, jQuery.makeArray(data) );
				} else {
					q.push( data );
				}
			}
			return q || [];
		}
	},

	dequeue: function( elem, type ) {
		type = type || "fx";

		var queue = jQuery.queue( elem, type ),
			fn = queue.shift(),
			hooks = {};

		// If the fx queue is dequeued, always remove the progress sentinel
		if ( fn === "inprogress" ) {
			fn = queue.shift();
		}

		if ( fn ) {
			// Add a progress sentinel to prevent the fx queue from being
			// automatically dequeued
			if ( type === "fx" ) {
				queue.unshift( "inprogress" );
			}

			jQuery._data( elem, type + ".run", hooks );
			fn.call( elem, function() {
				jQuery.dequeue( elem, type );
			}, hooks );
		}

		if ( !queue.length ) {
			jQuery.removeData( elem, type + "queue " + type + ".run", true );
			handleQueueMarkDefer( elem, type, "queue" );
		}
	}
});

jQuery.fn.extend({
	queue: function( type, data ) {
		var setter = 2;

		if ( typeof type !== "string" ) {
			data = type;
			type = "fx";
			setter--;
		}

		if ( arguments.length < setter ) {
			return jQuery.queue( this[0], type );
		}

		return data === undefined ?
			this :
			this.each(function() {
				var queue = jQuery.queue( this, type, data );

				if ( type === "fx" && queue[0] !== "inprogress" ) {
					jQuery.dequeue( this, type );
				}
			});
	},
	dequeue: function( type ) {
		return this.each(function() {
			jQuery.dequeue( this, type );
		});
	},
	// Based off of the plugin by Clint Helfers, with permission.
	// http://blindsignals.com/index.php/2009/07/jquery-delay/
	delay: function( time, type ) {
		time = jQuery.fx ? jQuery.fx.speeds[ time ] || time : time;
		type = type || "fx";

		return this.queue( type, function( next, hooks ) {
			var timeout = setTimeout( next, time );
			hooks.stop = function() {
				clearTimeout( timeout );
			};
		});
	},
	clearQueue: function( type ) {
		return this.queue( type || "fx", [] );
	},
	// Get a promise resolved when queues of a certain type
	// are emptied (fx is the type by default)
	promise: function( type, object ) {
		if ( typeof type !== "string" ) {
			object = type;
			type = undefined;
		}
		type = type || "fx";
		var defer = jQuery.Deferred(),
			elements = this,
			i = elements.length,
			count = 1,
			deferDataKey = type + "defer",
			queueDataKey = type + "queue",
			markDataKey = type + "mark",
			tmp;
		function resolve() {
			if ( !( --count ) ) {
				defer.resolveWith( elements, [ elements ] );
			}
		}
		while( i-- ) {
			if (( tmp = jQuery.data( elements[ i ], deferDataKey, undefined, true ) ||
					( jQuery.data( elements[ i ], queueDataKey, undefined, true ) ||
						jQuery.data( elements[ i ], markDataKey, undefined, true ) ) &&
					jQuery.data( elements[ i ], deferDataKey, jQuery.Callbacks( "once memory" ), true ) )) {
				count++;
				tmp.add( resolve );
			}
		}
		resolve();
		return defer.promise( object );
	}
});




var rclass = /[\n\t\r]/g,
	rspace = /\s+/,
	rreturn = /\r/g,
	rtype = /^(?:button|input)$/i,
	rfocusable = /^(?:button|input|object|select|textarea)$/i,
	rclickable = /^a(?:rea)?$/i,
	rboolean = /^(?:autofocus|autoplay|async|checked|controls|defer|disabled|hidden|loop|multiple|open|readonly|required|scoped|selected)$/i,
	getSetAttribute = jQuery.support.getSetAttribute,
	nodeHook, boolHook, fixSpecified;

jQuery.fn.extend({
	attr: function( name, value ) {
		return jQuery.access( this, jQuery.attr, name, value, arguments.length > 1 );
	},

	removeAttr: function( name ) {
		return this.each(function() {
			jQuery.removeAttr( this, name );
		});
	},

	prop: function( name, value ) {
		return jQuery.access( this, jQuery.prop, name, value, arguments.length > 1 );
	},

	removeProp: function( name ) {
		name = jQuery.propFix[ name ] || name;
		return this.each(function() {
			// try/catch handles cases where IE balks (such as removing a property on window)
			try {
				this[ name ] = undefined;
				delete this[ name ];
			} catch( e ) {}
		});
	},

	addClass: function( value ) {
		var classNames, i, l, elem,
			setClass, c, cl;

		if ( jQuery.isFunction( value ) ) {
			return this.each(function( j ) {
				jQuery( this ).addClass( value.call(this, j, this.className) );
			});
		}

		if ( value && typeof value === "string" ) {
			classNames = value.split( rspace );

			for ( i = 0, l = this.length; i < l; i++ ) {
				elem = this[ i ];

				if ( elem.nodeType === 1 ) {
					if ( !elem.className && classNames.length === 1 ) {
						elem.className = value;

					} else {
						setClass = " " + elem.className + " ";

						for ( c = 0, cl = classNames.length; c < cl; c++ ) {
							if ( !~setClass.indexOf( " " + classNames[ c ] + " " ) ) {
								setClass += classNames[ c ] + " ";
							}
						}
						elem.className = jQuery.trim( setClass );
					}
				}
			}
		}

		return this;
	},

	removeClass: function( value ) {
		var classNames, i, l, elem, className, c, cl;

		if ( jQuery.isFunction( value ) ) {
			return this.each(function( j ) {
				jQuery( this ).removeClass( value.call(this, j, this.className) );
			});
		}

		if ( (value && typeof value === "string") || value === undefined ) {
			classNames = ( value || "" ).split( rspace );

			for ( i = 0, l = this.length; i < l; i++ ) {
				elem = this[ i ];

				if ( elem.nodeType === 1 && elem.className ) {
					if ( value ) {
						className = (" " + elem.className + " ").replace( rclass, " " );
						for ( c = 0, cl = classNames.length; c < cl; c++ ) {
							className = className.replace(" " + classNames[ c ] + " ", " ");
						}
						elem.className = jQuery.trim( className );

					} else {
						elem.className = "";
					}
				}
			}
		}

		return this;
	},

	toggleClass: function( value, stateVal ) {
		var type = typeof value,
			isBool = typeof stateVal === "boolean";

		if ( jQuery.isFunction( value ) ) {
			return this.each(function( i ) {
				jQuery( this ).toggleClass( value.call(this, i, this.className, stateVal), stateVal );
			});
		}

		return this.each(function() {
			if ( type === "string" ) {
				// toggle individual class names
				var className,
					i = 0,
					self = jQuery( this ),
					state = stateVal,
					classNames = value.split( rspace );

				while ( (className = classNames[ i++ ]) ) {
					// check each className given, space seperated list
					state = isBool ? state : !self.hasClass( className );
					self[ state ? "addClass" : "removeClass" ]( className );
				}

			} else if ( type === "undefined" || type === "boolean" ) {
				if ( this.className ) {
					// store className if set
					jQuery._data( this, "__className__", this.className );
				}

				// toggle whole className
				this.className = this.className || value === false ? "" : jQuery._data( this, "__className__" ) || "";
			}
		});
	},

	hasClass: function( selector ) {
		var className = " " + selector + " ",
			i = 0,
			l = this.length;
		for ( ; i < l; i++ ) {
			if ( this[i].nodeType === 1 && (" " + this[i].className + " ").replace(rclass, " ").indexOf( className ) > -1 ) {
				return true;
			}
		}

		return false;
	},

	val: function( value ) {
		var hooks, ret, isFunction,
			elem = this[0];

		if ( !arguments.length ) {
			if ( elem ) {
				hooks = jQuery.valHooks[ elem.type ] || jQuery.valHooks[ elem.nodeName.toLowerCase() ];

				if ( hooks && "get" in hooks && (ret = hooks.get( elem, "value" )) !== undefined ) {
					return ret;
				}

				ret = elem.value;

				return typeof ret === "string" ?
					// handle most common string cases
					ret.replace(rreturn, "") :
					// handle cases where value is null/undef or number
					ret == null ? "" : ret;
			}

			return;
		}

		isFunction = jQuery.isFunction( value );

		return this.each(function( i ) {
			var self = jQuery(this), val;

			if ( this.nodeType !== 1 ) {
				return;
			}

			if ( isFunction ) {
				val = value.call( this, i, self.val() );
			} else {
				val = value;
			}

			// Treat null/undefined as ""; convert numbers to string
			if ( val == null ) {
				val = "";
			} else if ( typeof val === "number" ) {
				val += "";
			} else if ( jQuery.isArray( val ) ) {
				val = jQuery.map(val, function ( value ) {
					return value == null ? "" : value + "";
				});
			}

			hooks = jQuery.valHooks[ this.type ] || jQuery.valHooks[ this.nodeName.toLowerCase() ];

			// If set returns undefined, fall back to normal setting
			if ( !hooks || !("set" in hooks) || hooks.set( this, val, "value" ) === undefined ) {
				this.value = val;
			}
		});
	}
});

jQuery.extend({
	valHooks: {
		option: {
			get: function( elem ) {
				// attributes.value is undefined in Blackberry 4.7 but
				// uses .value. See #6932
				var val = elem.attributes.value;
				return !val || val.specified ? elem.value : elem.text;
			}
		},
		select: {
			get: function( elem ) {
				var value, i, max, option,
					index = elem.selectedIndex,
					values = [],
					options = elem.options,
					one = elem.type === "select-one";

				// Nothing was selected
				if ( index < 0 ) {
					return null;
				}

				// Loop through all the selected options
				i = one ? index : 0;
				max = one ? index + 1 : options.length;
				for ( ; i < max; i++ ) {
					option = options[ i ];

					// Don't return options that are disabled or in a disabled optgroup
					if ( option.selected && (jQuery.support.optDisabled ? !option.disabled : option.getAttribute("disabled") === null) &&
							(!option.parentNode.disabled || !jQuery.nodeName( option.parentNode, "optgroup" )) ) {

						// Get the specific value for the option
						value = jQuery( option ).val();

						// We don't need an array for one selects
						if ( one ) {
							return value;
						}

						// Multi-Selects return an array
						values.push( value );
					}
				}

				// Fixes Bug #2551 -- select.val() broken in IE after form.reset()
				if ( one && !values.length && options.length ) {
					return jQuery( options[ index ] ).val();
				}

				return values;
			},

			set: function( elem, value ) {
				var values = jQuery.makeArray( value );

				jQuery(elem).find("option").each(function() {
					this.selected = jQuery.inArray( jQuery(this).val(), values ) >= 0;
				});

				if ( !values.length ) {
					elem.selectedIndex = -1;
				}
				return values;
			}
		}
	},

	attrFn: {
		val: true,
		css: true,
		html: true,
		text: true,
		data: true,
		width: true,
		height: true,
		offset: true
	},

	attr: function( elem, name, value, pass ) {
		var ret, hooks, notxml,
			nType = elem.nodeType;

		// don't get/set attributes on text, comment and attribute nodes
		if ( !elem || nType === 3 || nType === 8 || nType === 2 ) {
			return;
		}

		if ( pass && name in jQuery.attrFn ) {
			return jQuery( elem )[ name ]( value );
		}

		// Fallback to prop when attributes are not supported
		if ( typeof elem.getAttribute === "undefined" ) {
			return jQuery.prop( elem, name, value );
		}

		notxml = nType !== 1 || !jQuery.isXMLDoc( elem );

		// All attributes are lowercase
		// Grab necessary hook if one is defined
		if ( notxml ) {
			name = name.toLowerCase();
			hooks = jQuery.attrHooks[ name ] || ( rboolean.test( name ) ? boolHook : nodeHook );
		}

		if ( value !== undefined ) {

			if ( value === null ) {
				jQuery.removeAttr( elem, name );
				return;

			} else if ( hooks && "set" in hooks && notxml && (ret = hooks.set( elem, value, name )) !== undefined ) {
				return ret;

			} else {
				elem.setAttribute( name, "" + value );
				return value;
			}

		} else if ( hooks && "get" in hooks && notxml && (ret = hooks.get( elem, name )) !== null ) {
			return ret;

		} else {

			ret = elem.getAttribute( name );

			// Non-existent attributes return null, we normalize to undefined
			return ret === null ?
				undefined :
				ret;
		}
	},

	removeAttr: function( elem, value ) {
		var propName, attrNames, name, l, isBool,
			i = 0;

		if ( value && elem.nodeType === 1 ) {
			attrNames = value.toLowerCase().split( rspace );
			l = attrNames.length;

			for ( ; i < l; i++ ) {
				name = attrNames[ i ];

				if ( name ) {
					propName = jQuery.propFix[ name ] || name;
					isBool = rboolean.test( name );

					// See #9699 for explanation of this approach (setting first, then removal)
					// Do not do this for boolean attributes (see #10870)
					if ( !isBool ) {
						jQuery.attr( elem, name, "" );
					}
					elem.removeAttribute( getSetAttribute ? name : propName );

					// Set corresponding property to false for boolean attributes
					if ( isBool && propName in elem ) {
						elem[ propName ] = false;
					}
				}
			}
		}
	},

	attrHooks: {
		type: {
			set: function( elem, value ) {
				// We can't allow the type property to be changed (since it causes problems in IE)
				if ( rtype.test( elem.nodeName ) && elem.parentNode ) {
					jQuery.error( "type property can't be changed" );
				} else if ( !jQuery.support.radioValue && value === "radio" && jQuery.nodeName(elem, "input") ) {
					// Setting the type on a radio button after the value resets the value in IE6-9
					// Reset value to it's default in case type is set after value
					// This is for element creation
					var val = elem.value;
					elem.setAttribute( "type", value );
					if ( val ) {
						elem.value = val;
					}
					return value;
				}
			}
		},
		// Use the value property for back compat
		// Use the nodeHook for button elements in IE6/7 (#1954)
		value: {
			get: function( elem, name ) {
				if ( nodeHook && jQuery.nodeName( elem, "button" ) ) {
					return nodeHook.get( elem, name );
				}
				return name in elem ?
					elem.value :
					null;
			},
			set: function( elem, value, name ) {
				if ( nodeHook && jQuery.nodeName( elem, "button" ) ) {
					return nodeHook.set( elem, value, name );
				}
				// Does not return so that setAttribute is also used
				elem.value = value;
			}
		}
	},

	propFix: {
		tabindex: "tabIndex",
		readonly: "readOnly",
		"for": "htmlFor",
		"class": "className",
		maxlength: "maxLength",
		cellspacing: "cellSpacing",
		cellpadding: "cellPadding",
		rowspan: "rowSpan",
		colspan: "colSpan",
		usemap: "useMap",
		frameborder: "frameBorder",
		contenteditable: "contentEditable"
	},

	prop: function( elem, name, value ) {
		var ret, hooks, notxml,
			nType = elem.nodeType;

		// don't get/set properties on text, comment and attribute nodes
		if ( !elem || nType === 3 || nType === 8 || nType === 2 ) {
			return;
		}

		notxml = nType !== 1 || !jQuery.isXMLDoc( elem );

		if ( notxml ) {
			// Fix name and attach hooks
			name = jQuery.propFix[ name ] || name;
			hooks = jQuery.propHooks[ name ];
		}

		if ( value !== undefined ) {
			if ( hooks && "set" in hooks && (ret = hooks.set( elem, value, name )) !== undefined ) {
				return ret;

			} else {
				return ( elem[ name ] = value );
			}

		} else {
			if ( hooks && "get" in hooks && (ret = hooks.get( elem, name )) !== null ) {
				return ret;

			} else {
				return elem[ name ];
			}
		}
	},

	propHooks: {
		tabIndex: {
			get: function( elem ) {
				// elem.tabIndex doesn't always return the correct value when it hasn't been explicitly set
				// http://fluidproject.org/blog/2008/01/09/getting-setting-and-removing-tabindex-values-with-javascript/
				var attributeNode = elem.getAttributeNode("tabindex");

				return attributeNode && attributeNode.specified ?
					parseInt( attributeNode.value, 10 ) :
					rfocusable.test( elem.nodeName ) || rclickable.test( elem.nodeName ) && elem.href ?
						0 :
						undefined;
			}
		}
	}
});

// Add the tabIndex propHook to attrHooks for back-compat (different case is intentional)
jQuery.attrHooks.tabindex = jQuery.propHooks.tabIndex;

// Hook for boolean attributes
boolHook = {
	get: function( elem, name ) {
		// Align boolean attributes with corresponding properties
		// Fall back to attribute presence where some booleans are not supported
		var attrNode,
			property = jQuery.prop( elem, name );
		return property === true || typeof property !== "boolean" && ( attrNode = elem.getAttributeNode(name) ) && attrNode.nodeValue !== false ?
			name.toLowerCase() :
			undefined;
	},
	set: function( elem, value, name ) {
		var propName;
		if ( value === false ) {
			// Remove boolean attributes when set to false
			jQuery.removeAttr( elem, name );
		} else {
			// value is true since we know at this point it's type boolean and not false
			// Set boolean attributes to the same name and set the DOM property
			propName = jQuery.propFix[ name ] || name;
			if ( propName in elem ) {
				// Only set the IDL specifically if it already exists on the element
				elem[ propName ] = true;
			}

			elem.setAttribute( name, name.toLowerCase() );
		}
		return name;
	}
};

// IE6/7 do not support getting/setting some attributes with get/setAttribute
if ( !getSetAttribute ) {

	fixSpecified = {
		name: true,
		id: true,
		coords: true
	};

	// Use this for any attribute in IE6/7
	// This fixes almost every IE6/7 issue
	nodeHook = jQuery.valHooks.button = {
		get: function( elem, name ) {
			var ret;
			ret = elem.getAttributeNode( name );
			return ret && ( fixSpecified[ name ] ? ret.nodeValue !== "" : ret.specified ) ?
				ret.nodeValue :
				undefined;
		},
		set: function( elem, value, name ) {
			// Set the existing or create a new attribute node
			var ret = elem.getAttributeNode( name );
			if ( !ret ) {
				ret = document.createAttribute( name );
				elem.setAttributeNode( ret );
			}
			return ( ret.nodeValue = value + "" );
		}
	};

	// Apply the nodeHook to tabindex
	jQuery.attrHooks.tabindex.set = nodeHook.set;

	// Set width and height to auto instead of 0 on empty string( Bug #8150 )
	// This is for removals
	jQuery.each([ "width", "height" ], function( i, name ) {
		jQuery.attrHooks[ name ] = jQuery.extend( jQuery.attrHooks[ name ], {
			set: function( elem, value ) {
				if ( value === "" ) {
					elem.setAttribute( name, "auto" );
					return value;
				}
			}
		});
	});

	// Set contenteditable to false on removals(#10429)
	// Setting to empty string throws an error as an invalid value
	jQuery.attrHooks.contenteditable = {
		get: nodeHook.get,
		set: function( elem, value, name ) {
			if ( value === "" ) {
				value = "false";
			}
			nodeHook.set( elem, value, name );
		}
	};
}


// Some attributes require a special call on IE
if ( !jQuery.support.hrefNormalized ) {
	jQuery.each([ "href", "src", "width", "height" ], function( i, name ) {
		jQuery.attrHooks[ name ] = jQuery.extend( jQuery.attrHooks[ name ], {
			get: function( elem ) {
				var ret = elem.getAttribute( name, 2 );
				return ret === null ? undefined : ret;
			}
		});
	});
}

if ( !jQuery.support.style ) {
	jQuery.attrHooks.style = {
		get: function( elem ) {
			// Return undefined in the case of empty string
			// Normalize to lowercase since IE uppercases css property names
			return elem.style.cssText.toLowerCase() || undefined;
		},
		set: function( elem, value ) {
			return ( elem.style.cssText = "" + value );
		}
	};
}

// Safari mis-reports the default selected property of an option
// Accessing the parent's selectedIndex property fixes it
if ( !jQuery.support.optSelected ) {
	jQuery.propHooks.selected = jQuery.extend( jQuery.propHooks.selected, {
		get: function( elem ) {
			var parent = elem.parentNode;

			if ( parent ) {
				parent.selectedIndex;

				// Make sure that it also works with optgroups, see #5701
				if ( parent.parentNode ) {
					parent.parentNode.selectedIndex;
				}
			}
			return null;
		}
	});
}

// IE6/7 call enctype encoding
if ( !jQuery.support.enctype ) {
	jQuery.propFix.enctype = "encoding";
}

// Radios and checkboxes getter/setter
if ( !jQuery.support.checkOn ) {
	jQuery.each([ "radio", "checkbox" ], function() {
		jQuery.valHooks[ this ] = {
			get: function( elem ) {
				// Handle the case where in Webkit "" is returned instead of "on" if a value isn't specified
				return elem.getAttribute("value") === null ? "on" : elem.value;
			}
		};
	});
}
jQuery.each([ "radio", "checkbox" ], function() {
	jQuery.valHooks[ this ] = jQuery.extend( jQuery.valHooks[ this ], {
		set: function( elem, value ) {
			if ( jQuery.isArray( value ) ) {
				return ( elem.checked = jQuery.inArray( jQuery(elem).val(), value ) >= 0 );
			}
		}
	});
});




var rformElems = /^(?:textarea|input|select)$/i,
	rtypenamespace = /^([^\.]*)?(?:\.(.+))?$/,
	rhoverHack = /(?:^|\s)hover(\.\S+)?\b/,
	rkeyEvent = /^key/,
	rmouseEvent = /^(?:mouse|contextmenu)|click/,
	rfocusMorph = /^(?:focusinfocus|focusoutblur)$/,
	rquickIs = /^(\w*)(?:#([\w\-]+))?(?:\.([\w\-]+))?$/,
	quickParse = function( selector ) {
		var quick = rquickIs.exec( selector );
		if ( quick ) {
			//   0  1    2   3
			// [ _, tag, id, class ]
			quick[1] = ( quick[1] || "" ).toLowerCase();
			quick[3] = quick[3] && new RegExp( "(?:^|\\s)" + quick[3] + "(?:\\s|$)" );
		}
		return quick;
	},
	quickIs = function( elem, m ) {
		var attrs = elem.attributes || {};
		return (
			(!m[1] || elem.nodeName.toLowerCase() === m[1]) &&
			(!m[2] || (attrs.id || {}).value === m[2]) &&
			(!m[3] || m[3].test( (attrs[ "class" ] || {}).value ))
		);
	},
	hoverHack = function( events ) {
		return jQuery.event.special.hover ? events : events.replace( rhoverHack, "mouseenter$1 mouseleave$1" );
	};

/*
 * Helper functions for managing events -- not part of the public interface.
 * Props to Dean Edwards' addEvent library for many of the ideas.
 */
jQuery.event = {

	add: function( elem, types, handler, data, selector ) {

		var elemData, eventHandle, events,
			t, tns, type, namespaces, handleObj,
			handleObjIn, quick, handlers, special;

		// Don't attach events to noData or text/comment nodes (allow plain objects tho)
		if ( elem.nodeType === 3 || elem.nodeType === 8 || !types || !handler || !(elemData = jQuery._data( elem )) ) {
			return;
		}

		// Caller can pass in an object of custom data in lieu of the handler
		if ( handler.handler ) {
			handleObjIn = handler;
			handler = handleObjIn.handler;
			selector = handleObjIn.selector;
		}

		// Make sure that the handler has a unique ID, used to find/remove it later
		if ( !handler.guid ) {
			handler.guid = jQuery.guid++;
		}

		// Init the element's event structure and main handler, if this is the first
		events = elemData.events;
		if ( !events ) {
			elemData.events = events = {};
		}
		eventHandle = elemData.handle;
		if ( !eventHandle ) {
			elemData.handle = eventHandle = function( e ) {
				// Discard the second event of a jQuery.event.trigger() and
				// when an event is called after a page has unloaded
				return typeof jQuery !== "undefined" && (!e || jQuery.event.triggered !== e.type) ?
					jQuery.event.dispatch.apply( eventHandle.elem, arguments ) :
					undefined;
			};
			// Add elem as a property of the handle fn to prevent a memory leak with IE non-native events
			eventHandle.elem = elem;
		}

		// Handle multiple events separated by a space
		// jQuery(...).bind("mouseover mouseout", fn);
		types = jQuery.trim( hoverHack(types) ).split( " " );
		for ( t = 0; t < types.length; t++ ) {

			tns = rtypenamespace.exec( types[t] ) || [];
			type = tns[1];
			namespaces = ( tns[2] || "" ).split( "." ).sort();

			// If event changes its type, use the special event handlers for the changed type
			special = jQuery.event.special[ type ] || {};

			// If selector defined, determine special event api type, otherwise given type
			type = ( selector ? special.delegateType : special.bindType ) || type;

			// Update special based on newly reset type
			special = jQuery.event.special[ type ] || {};

			// handleObj is passed to all event handlers
			handleObj = jQuery.extend({
				type: type,
				origType: tns[1],
				data: data,
				handler: handler,
				guid: handler.guid,
				selector: selector,
				quick: selector && quickParse( selector ),
				namespace: namespaces.join(".")
			}, handleObjIn );

			// Init the event handler queue if we're the first
			handlers = events[ type ];
			if ( !handlers ) {
				handlers = events[ type ] = [];
				handlers.delegateCount = 0;

				// Only use addEventListener/attachEvent if the special events handler returns false
				if ( !special.setup || special.setup.call( elem, data, namespaces, eventHandle ) === false ) {
					// Bind the global event handler to the element
					if ( elem.addEventListener ) {
						elem.addEventListener( type, eventHandle, false );

					} else if ( elem.attachEvent ) {
						elem.attachEvent( "on" + type, eventHandle );
					}
				}
			}

			if ( special.add ) {
				special.add.call( elem, handleObj );

				if ( !handleObj.handler.guid ) {
					handleObj.handler.guid = handler.guid;
				}
			}

			// Add to the element's handler list, delegates in front
			if ( selector ) {
				handlers.splice( handlers.delegateCount++, 0, handleObj );
			} else {
				handlers.push( handleObj );
			}

			// Keep track of which events have ever been used, for event optimization
			jQuery.event.global[ type ] = true;
		}

		// Nullify elem to prevent memory leaks in IE
		elem = null;
	},

	global: {},

	// Detach an event or set of events from an element
	remove: function( elem, types, handler, selector, mappedTypes ) {

		var elemData = jQuery.hasData( elem ) && jQuery._data( elem ),
			t, tns, type, origType, namespaces, origCount,
			j, events, special, handle, eventType, handleObj;

		if ( !elemData || !(events = elemData.events) ) {
			return;
		}

		// Once for each type.namespace in types; type may be omitted
		types = jQuery.trim( hoverHack( types || "" ) ).split(" ");
		for ( t = 0; t < types.length; t++ ) {
			tns = rtypenamespace.exec( types[t] ) || [];
			type = origType = tns[1];
			namespaces = tns[2];

			// Unbind all events (on this namespace, if provided) for the element
			if ( !type ) {
				for ( type in events ) {
					jQuery.event.remove( elem, type + types[ t ], handler, selector, true );
				}
				continue;
			}

			special = jQuery.event.special[ type ] || {};
			type = ( selector? special.delegateType : special.bindType ) || type;
			eventType = events[ type ] || [];
			origCount = eventType.length;
			namespaces = namespaces ? new RegExp("(^|\\.)" + namespaces.split(".").sort().join("\\.(?:.*\\.)?") + "(\\.|$)") : null;

			// Remove matching events
			for ( j = 0; j < eventType.length; j++ ) {
				handleObj = eventType[ j ];

				if ( ( mappedTypes || origType === handleObj.origType ) &&
					 ( !handler || handler.guid === handleObj.guid ) &&
					 ( !namespaces || namespaces.test( handleObj.namespace ) ) &&
					 ( !selector || selector === handleObj.selector || selector === "**" && handleObj.selector ) ) {
					eventType.splice( j--, 1 );

					if ( handleObj.selector ) {
						eventType.delegateCount--;
					}
					if ( special.remove ) {
						special.remove.call( elem, handleObj );
					}
				}
			}

			// Remove generic event handler if we removed something and no more handlers exist
			// (avoids potential for endless recursion during removal of special event handlers)
			if ( eventType.length === 0 && origCount !== eventType.length ) {
				if ( !special.teardown || special.teardown.call( elem, namespaces ) === false ) {
					jQuery.removeEvent( elem, type, elemData.handle );
				}

				delete events[ type ];
			}
		}

		// Remove the expando if it's no longer used
		if ( jQuery.isEmptyObject( events ) ) {
			handle = elemData.handle;
			if ( handle ) {
				handle.elem = null;
			}

			// removeData also checks for emptiness and clears the expando if empty
			// so use it instead of delete
			jQuery.removeData( elem, [ "events", "handle" ], true );
		}
	},

	// Events that are safe to short-circuit if no handlers are attached.
	// Native DOM events should not be added, they may have inline handlers.
	customEvent: {
		"getData": true,
		"setData": true,
		"changeData": true
	},

	trigger: function( event, data, elem, onlyHandlers ) {
		// Don't do events on text and comment nodes
		if ( elem && (elem.nodeType === 3 || elem.nodeType === 8) ) {
			return;
		}

		// Event object or event type
		var type = event.type || event,
			namespaces = [],
			cache, exclusive, i, cur, old, ontype, special, handle, eventPath, bubbleType;

		// focus/blur morphs to focusin/out; ensure we're not firing them right now
		if ( rfocusMorph.test( type + jQuery.event.triggered ) ) {
			return;
		}

		if ( type.indexOf( "!" ) >= 0 ) {
			// Exclusive events trigger only for the exact event (no namespaces)
			type = type.slice(0, -1);
			exclusive = true;
		}

		if ( type.indexOf( "." ) >= 0 ) {
			// Namespaced trigger; create a regexp to match event type in handle()
			namespaces = type.split(".");
			type = namespaces.shift();
			namespaces.sort();
		}

		if ( (!elem || jQuery.event.customEvent[ type ]) && !jQuery.event.global[ type ] ) {
			// No jQuery handlers for this event type, and it can't have inline handlers
			return;
		}

		// Caller can pass in an Event, Object, or just an event type string
		event = typeof event === "object" ?
			// jQuery.Event object
			event[ jQuery.expando ] ? event :
			// Object literal
			new jQuery.Event( type, event ) :
			// Just the event type (string)
			new jQuery.Event( type );

		event.type = type;
		event.isTrigger = true;
		event.exclusive = exclusive;
		event.namespace = namespaces.join( "." );
		event.namespace_re = event.namespace? new RegExp("(^|\\.)" + namespaces.join("\\.(?:.*\\.)?") + "(\\.|$)") : null;
		ontype = type.indexOf( ":" ) < 0 ? "on" + type : "";

		// Handle a global trigger
		if ( !elem ) {

			// TODO: Stop taunting the data cache; remove global events and always attach to document
			cache = jQuery.cache;
			for ( i in cache ) {
				if ( cache[ i ].events && cache[ i ].events[ type ] ) {
					jQuery.event.trigger( event, data, cache[ i ].handle.elem, true );
				}
			}
			return;
		}

		// Clean up the event in case it is being reused
		event.result = undefined;
		if ( !event.target ) {
			event.target = elem;
		}

		// Clone any incoming data and prepend the event, creating the handler arg list
		data = data != null ? jQuery.makeArray( data ) : [];
		data.unshift( event );

		// Allow special events to draw outside the lines
		special = jQuery.event.special[ type ] || {};
		if ( special.trigger && special.trigger.apply( elem, data ) === false ) {
			return;
		}

		// Determine event propagation path in advance, per W3C events spec (#9951)
		// Bubble up to document, then to window; watch for a global ownerDocument var (#9724)
		eventPath = [[ elem, special.bindType || type ]];
		if ( !onlyHandlers && !special.noBubble && !jQuery.isWindow( elem ) ) {

			bubbleType = special.delegateType || type;
			cur = rfocusMorph.test( bubbleType + type ) ? elem : elem.parentNode;
			old = null;
			for ( ; cur; cur = cur.parentNode ) {
				eventPath.push([ cur, bubbleType ]);
				old = cur;
			}

			// Only add window if we got to document (e.g., not plain obj or detached DOM)
			if ( old && old === elem.ownerDocument ) {
				eventPath.push([ old.defaultView || old.parentWindow || window, bubbleType ]);
			}
		}

		// Fire handlers on the event path
		for ( i = 0; i < eventPath.length && !event.isPropagationStopped(); i++ ) {

			cur = eventPath[i][0];
			event.type = eventPath[i][1];

			handle = ( jQuery._data( cur, "events" ) || {} )[ event.type ] && jQuery._data( cur, "handle" );
			if ( handle ) {
				handle.apply( cur, data );
			}
			// Note that this is a bare JS function and not a jQuery handler
			handle = ontype && cur[ ontype ];
			if ( handle && jQuery.acceptData( cur ) && handle.apply( cur, data ) === false ) {
				event.preventDefault();
			}
		}
		event.type = type;

		// If nobody prevented the default action, do it now
		if ( !onlyHandlers && !event.isDefaultPrevented() ) {

			if ( (!special._default || special._default.apply( elem.ownerDocument, data ) === false) &&
				!(type === "click" && jQuery.nodeName( elem, "a" )) && jQuery.acceptData( elem ) ) {

				// Call a native DOM method on the target with the same name name as the event.
				// Can't use an .isFunction() check here because IE6/7 fails that test.
				// Don't do default actions on window, that's where global variables be (#6170)
				// IE<9 dies on focus/blur to hidden element (#1486)
				if ( ontype && elem[ type ] && ((type !== "focus" && type !== "blur") || event.target.offsetWidth !== 0) && !jQuery.isWindow( elem ) ) {

					// Don't re-trigger an onFOO event when we call its FOO() method
					old = elem[ ontype ];

					if ( old ) {
						elem[ ontype ] = null;
					}

					// Prevent re-triggering of the same event, since we already bubbled it above
					jQuery.event.triggered = type;
					elem[ type ]();
					jQuery.event.triggered = undefined;

					if ( old ) {
						elem[ ontype ] = old;
					}
				}
			}
		}

		return event.result;
	},

	dispatch: function( event ) {

		// Make a writable jQuery.Event from the native event object
		event = jQuery.event.fix( event || window.event );

		var handlers = ( (jQuery._data( this, "events" ) || {} )[ event.type ] || []),
			delegateCount = handlers.delegateCount,
			args = [].slice.call( arguments, 0 ),
			run_all = !event.exclusive && !event.namespace,
			special = jQuery.event.special[ event.type ] || {},
			handlerQueue = [],
			i, j, cur, jqcur, ret, selMatch, matched, matches, handleObj, sel, related;

		// Use the fix-ed jQuery.Event rather than the (read-only) native event
		args[0] = event;
		event.delegateTarget = this;

		// Call the preDispatch hook for the mapped type, and let it bail if desired
		if ( special.preDispatch && special.preDispatch.call( this, event ) === false ) {
			return;
		}

		// Determine handlers that should run if there are delegated events
		// Avoid non-left-click bubbling in Firefox (#3861)
		if ( delegateCount && !(event.button && event.type === "click") ) {

			// Pregenerate a single jQuery object for reuse with .is()
			jqcur = jQuery(this);
			jqcur.context = this.ownerDocument || this;

			for ( cur = event.target; cur != this; cur = cur.parentNode || this ) {

				// Don't process events on disabled elements (#6911, #8165)
				if ( cur.disabled !== true ) {
					selMatch = {};
					matches = [];
					jqcur[0] = cur;
					for ( i = 0; i < delegateCount; i++ ) {
						handleObj = handlers[ i ];
						sel = handleObj.selector;

						if ( selMatch[ sel ] === undefined ) {
							selMatch[ sel ] = (
								handleObj.quick ? quickIs( cur, handleObj.quick ) : jqcur.is( sel )
							);
						}
						if ( selMatch[ sel ] ) {
							matches.push( handleObj );
						}
					}
					if ( matches.length ) {
						handlerQueue.push({ elem: cur, matches: matches });
					}
				}
			}
		}

		// Add the remaining (directly-bound) handlers
		if ( handlers.length > delegateCount ) {
			handlerQueue.push({ elem: this, matches: handlers.slice( delegateCount ) });
		}

		// Run delegates first; they may want to stop propagation beneath us
		for ( i = 0; i < handlerQueue.length && !event.isPropagationStopped(); i++ ) {
			matched = handlerQueue[ i ];
			event.currentTarget = matched.elem;

			for ( j = 0; j < matched.matches.length && !event.isImmediatePropagationStopped(); j++ ) {
				handleObj = matched.matches[ j ];

				// Triggered event must either 1) be non-exclusive and have no namespace, or
				// 2) have namespace(s) a subset or equal to those in the bound event (both can have no namespace).
				if ( run_all || (!event.namespace && !handleObj.namespace) || event.namespace_re && event.namespace_re.test( handleObj.namespace ) ) {

					event.data = handleObj.data;
					event.handleObj = handleObj;

					ret = ( (jQuery.event.special[ handleObj.origType ] || {}).handle || handleObj.handler )
							.apply( matched.elem, args );

					if ( ret !== undefined ) {
						event.result = ret;
						if ( ret === false ) {
							event.preventDefault();
							event.stopPropagation();
						}
					}
				}
			}
		}

		// Call the postDispatch hook for the mapped type
		if ( special.postDispatch ) {
			special.postDispatch.call( this, event );
		}

		return event.result;
	},

	// Includes some event props shared by KeyEvent and MouseEvent
	// *** attrChange attrName relatedNode srcElement  are not normalized, non-W3C, deprecated, will be removed in 1.8 ***
	props: "attrChange attrName relatedNode srcElement altKey bubbles cancelable ctrlKey currentTarget eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),

	fixHooks: {},

	keyHooks: {
		props: "char charCode key keyCode".split(" "),
		filter: function( event, original ) {

			// Add which for key events
			if ( event.which == null ) {
				event.which = original.charCode != null ? original.charCode : original.keyCode;
			}

			return event;
		}
	},

	mouseHooks: {
		props: "button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),
		filter: function( event, original ) {
			var eventDoc, doc, body,
				button = original.button,
				fromElement = original.fromElement;

			// Calculate pageX/Y if missing and clientX/Y available
			if ( event.pageX == null && original.clientX != null ) {
				eventDoc = event.target.ownerDocument || document;
				doc = eventDoc.documentElement;
				body = eventDoc.body;

				event.pageX = original.clientX + ( doc && doc.scrollLeft || body && body.scrollLeft || 0 ) - ( doc && doc.clientLeft || body && body.clientLeft || 0 );
				event.pageY = original.clientY + ( doc && doc.scrollTop  || body && body.scrollTop  || 0 ) - ( doc && doc.clientTop  || body && body.clientTop  || 0 );
			}

			// Add relatedTarget, if necessary
			if ( !event.relatedTarget && fromElement ) {
				event.relatedTarget = fromElement === event.target ? original.toElement : fromElement;
			}

			// Add which for click: 1 === left; 2 === middle; 3 === right
			// Note: button is not normalized, so don't use it
			if ( !event.which && button !== undefined ) {
				event.which = ( button & 1 ? 1 : ( button & 2 ? 3 : ( button & 4 ? 2 : 0 ) ) );
			}

			return event;
		}
	},

	fix: function( event ) {
		if ( event[ jQuery.expando ] ) {
			return event;
		}

		// Create a writable copy of the event object and normalize some properties
		var i, prop,
			originalEvent = event,
			fixHook = jQuery.event.fixHooks[ event.type ] || {},
			copy = fixHook.props ? this.props.concat( fixHook.props ) : this.props;

		event = jQuery.Event( originalEvent );

		for ( i = copy.length; i; ) {
			prop = copy[ --i ];
			event[ prop ] = originalEvent[ prop ];
		}

		// Fix target property, if necessary (#1925, IE 6/7/8 & Safari2)
		if ( !event.target ) {
			event.target = originalEvent.srcElement || document;
		}

		// Target should not be a text node (#504, Safari)
		if ( event.target.nodeType === 3 ) {
			event.target = event.target.parentNode;
		}

		// For mouse/key events; add metaKey if it's not there (#3368, IE6/7/8)
		if ( event.metaKey === undefined ) {
			event.metaKey = event.ctrlKey;
		}

		return fixHook.filter? fixHook.filter( event, originalEvent ) : event;
	},

	special: {
		ready: {
			// Make sure the ready event is setup
			setup: jQuery.bindReady
		},

		load: {
			// Prevent triggered image.load events from bubbling to window.load
			noBubble: true
		},

		focus: {
			delegateType: "focusin"
		},
		blur: {
			delegateType: "focusout"
		},

		beforeunload: {
			setup: function( data, namespaces, eventHandle ) {
				// We only want to do this special case on windows
				if ( jQuery.isWindow( this ) ) {
					this.onbeforeunload = eventHandle;
				}
			},

			teardown: function( namespaces, eventHandle ) {
				if ( this.onbeforeunload === eventHandle ) {
					this.onbeforeunload = null;
				}
			}
		}
	},

	simulate: function( type, elem, event, bubble ) {
		// Piggyback on a donor event to simulate a different one.
		// Fake originalEvent to avoid donor's stopPropagation, but if the
		// simulated event prevents default then we do the same on the donor.
		var e = jQuery.extend(
			new jQuery.Event(),
			event,
			{ type: type,
				isSimulated: true,
				originalEvent: {}
			}
		);
		if ( bubble ) {
			jQuery.event.trigger( e, null, elem );
		} else {
			jQuery.event.dispatch.call( elem, e );
		}
		if ( e.isDefaultPrevented() ) {
			event.preventDefault();
		}
	}
};

// Some plugins are using, but it's undocumented/deprecated and will be removed.
// The 1.7 special event interface should provide all the hooks needed now.
jQuery.event.handle = jQuery.event.dispatch;

jQuery.removeEvent = document.removeEventListener ?
	function( elem, type, handle ) {
		if ( elem.removeEventListener ) {
			elem.removeEventListener( type, handle, false );
		}
	} :
	function( elem, type, handle ) {
		if ( elem.detachEvent ) {
			elem.detachEvent( "on" + type, handle );
		}
	};

jQuery.Event = function( src, props ) {
	// Allow instantiation without the 'new' keyword
	if ( !(this instanceof jQuery.Event) ) {
		return new jQuery.Event( src, props );
	}

	// Event object
	if ( src && src.type ) {
		this.originalEvent = src;
		this.type = src.type;

		// Events bubbling up the document may have been marked as prevented
		// by a handler lower down the tree; reflect the correct value.
		this.isDefaultPrevented = ( src.defaultPrevented || src.returnValue === false ||
			src.getPreventDefault && src.getPreventDefault() ) ? returnTrue : returnFalse;

	// Event type
	} else {
		this.type = src;
	}

	// Put explicitly provided properties onto the event object
	if ( props ) {
		jQuery.extend( this, props );
	}

	// Create a timestamp if incoming event doesn't have one
	this.timeStamp = src && src.timeStamp || jQuery.now();

	// Mark it as fixed
	this[ jQuery.expando ] = true;
};

function returnFalse() {
	return false;
}
function returnTrue() {
	return true;
}

// jQuery.Event is based on DOM3 Events as specified by the ECMAScript Language Binding
// http://www.w3.org/TR/2003/WD-DOM-Level-3-Events-20030331/ecma-script-binding.html
jQuery.Event.prototype = {
	preventDefault: function() {
		this.isDefaultPrevented = returnTrue;

		var e = this.originalEvent;
		if ( !e ) {
			return;
		}

		// if preventDefault exists run it on the original event
		if ( e.preventDefault ) {
			e.preventDefault();

		// otherwise set the returnValue property of the original event to false (IE)
		} else {
			e.returnValue = false;
		}
	},
	stopPropagation: function() {
		this.isPropagationStopped = returnTrue;

		var e = this.originalEvent;
		if ( !e ) {
			return;
		}
		// if stopPropagation exists run it on the original event
		if ( e.stopPropagation ) {
			e.stopPropagation();
		}
		// otherwise set the cancelBubble property of the original event to true (IE)
		e.cancelBubble = true;
	},
	stopImmediatePropagation: function() {
		this.isImmediatePropagationStopped = returnTrue;
		this.stopPropagation();
	},
	isDefaultPrevented: returnFalse,
	isPropagationStopped: returnFalse,
	isImmediatePropagationStopped: returnFalse
};

// Create mouseenter/leave events using mouseover/out and event-time checks
jQuery.each({
	mouseenter: "mouseover",
	mouseleave: "mouseout"
}, function( orig, fix ) {
	jQuery.event.special[ orig ] = {
		delegateType: fix,
		bindType: fix,

		handle: function( event ) {
			var target = this,
				related = event.relatedTarget,
				handleObj = event.handleObj,
				selector = handleObj.selector,
				ret;

			// For mousenter/leave call the handler if related is outside the target.
			// NB: No relatedTarget if the mouse left/entered the browser window
			if ( !related || (related !== target && !jQuery.contains( target, related )) ) {
				event.type = handleObj.origType;
				ret = handleObj.handler.apply( this, arguments );
				event.type = fix;
			}
			return ret;
		}
	};
});

// IE submit delegation
if ( !jQuery.support.submitBubbles ) {

	jQuery.event.special.submit = {
		setup: function() {
			// Only need this for delegated form submit events
			if ( jQuery.nodeName( this, "form" ) ) {
				return false;
			}

			// Lazy-add a submit handler when a descendant form may potentially be submitted
			jQuery.event.add( this, "click._submit keypress._submit", function( e ) {
				// Node name check avoids a VML-related crash in IE (#9807)
				var elem = e.target,
					form = jQuery.nodeName( elem, "input" ) || jQuery.nodeName( elem, "button" ) ? elem.form : undefined;
				if ( form && !form._submit_attached ) {
					jQuery.event.add( form, "submit._submit", function( event ) {
						event._submit_bubble = true;
					});
					form._submit_attached = true;
				}
			});
			// return undefined since we don't need an event listener
		},
		
		postDispatch: function( event ) {
			// If form was submitted by the user, bubble the event up the tree
			if ( event._submit_bubble ) {
				delete event._submit_bubble;
				if ( this.parentNode && !event.isTrigger ) {
					jQuery.event.simulate( "submit", this.parentNode, event, true );
				}
			}
		},

		teardown: function() {
			// Only need this for delegated form submit events
			if ( jQuery.nodeName( this, "form" ) ) {
				return false;
			}

			// Remove delegated handlers; cleanData eventually reaps submit handlers attached above
			jQuery.event.remove( this, "._submit" );
		}
	};
}

// IE change delegation and checkbox/radio fix
if ( !jQuery.support.changeBubbles ) {

	jQuery.event.special.change = {

		setup: function() {

			if ( rformElems.test( this.nodeName ) ) {
				// IE doesn't fire change on a check/radio until blur; trigger it on click
				// after a propertychange. Eat the blur-change in special.change.handle.
				// This still fires onchange a second time for check/radio after blur.
				if ( this.type === "checkbox" || this.type === "radio" ) {
					jQuery.event.add( this, "propertychange._change", function( event ) {
						if ( event.originalEvent.propertyName === "checked" ) {
							this._just_changed = true;
						}
					});
					jQuery.event.add( this, "click._change", function( event ) {
						if ( this._just_changed && !event.isTrigger ) {
							this._just_changed = false;
							jQuery.event.simulate( "change", this, event, true );
						}
					});
				}
				return false;
			}
			// Delegated event; lazy-add a change handler on descendant inputs
			jQuery.event.add( this, "beforeactivate._change", function( e ) {
				var elem = e.target;

				if ( rformElems.test( elem.nodeName ) && !elem._change_attached ) {
					jQuery.event.add( elem, "change._change", function( event ) {
						if ( this.parentNode && !event.isSimulated && !event.isTrigger ) {
							jQuery.event.simulate( "change", this.parentNode, event, true );
						}
					});
					elem._change_attached = true;
				}
			});
		},

		handle: function( event ) {
			var elem = event.target;

			// Swallow native change events from checkbox/radio, we already triggered them above
			if ( this !== elem || event.isSimulated || event.isTrigger || (elem.type !== "radio" && elem.type !== "checkbox") ) {
				return event.handleObj.handler.apply( this, arguments );
			}
		},

		teardown: function() {
			jQuery.event.remove( this, "._change" );

			return rformElems.test( this.nodeName );
		}
	};
}

// Create "bubbling" focus and blur events
if ( !jQuery.support.focusinBubbles ) {
	jQuery.each({ focus: "focusin", blur: "focusout" }, function( orig, fix ) {

		// Attach a single capturing handler while someone wants focusin/focusout
		var attaches = 0,
			handler = function( event ) {
				jQuery.event.simulate( fix, event.target, jQuery.event.fix( event ), true );
			};

		jQuery.event.special[ fix ] = {
			setup: function() {
				if ( attaches++ === 0 ) {
					document.addEventListener( orig, handler, true );
				}
			},
			teardown: function() {
				if ( --attaches === 0 ) {
					document.removeEventListener( orig, handler, true );
				}
			}
		};
	});
}

jQuery.fn.extend({

	on: function( types, selector, data, fn, /*INTERNAL*/ one ) {
		var origFn, type;

		// Types can be a map of types/handlers
		if ( typeof types === "object" ) {
			// ( types-Object, selector, data )
			if ( typeof selector !== "string" ) { // && selector != null
				// ( types-Object, data )
				data = data || selector;
				selector = undefined;
			}
			for ( type in types ) {
				this.on( type, selector, data, types[ type ], one );
			}
			return this;
		}

		if ( data == null && fn == null ) {
			// ( types, fn )
			fn = selector;
			data = selector = undefined;
		} else if ( fn == null ) {
			if ( typeof selector === "string" ) {
				// ( types, selector, fn )
				fn = data;
				data = undefined;
			} else {
				// ( types, data, fn )
				fn = data;
				data = selector;
				selector = undefined;
			}
		}
		if ( fn === false ) {
			fn = returnFalse;
		} else if ( !fn ) {
			return this;
		}

		if ( one === 1 ) {
			origFn = fn;
			fn = function( event ) {
				// Can use an empty set, since event contains the info
				jQuery().off( event );
				return origFn.apply( this, arguments );
			};
			// Use same guid so caller can remove using origFn
			fn.guid = origFn.guid || ( origFn.guid = jQuery.guid++ );
		}
		return this.each( function() {
			jQuery.event.add( this, types, fn, data, selector );
		});
	},
	one: function( types, selector, data, fn ) {
		return this.on( types, selector, data, fn, 1 );
	},
	off: function( types, selector, fn ) {
		if ( types && types.preventDefault && types.handleObj ) {
			// ( event )  dispatched jQuery.Event
			var handleObj = types.handleObj;
			jQuery( types.delegateTarget ).off(
				handleObj.namespace ? handleObj.origType + "." + handleObj.namespace : handleObj.origType,
				handleObj.selector,
				handleObj.handler
			);
			return this;
		}
		if ( typeof types === "object" ) {
			// ( types-object [, selector] )
			for ( var type in types ) {
				this.off( type, selector, types[ type ] );
			}
			return this;
		}
		if ( selector === false || typeof selector === "function" ) {
			// ( types [, fn] )
			fn = selector;
			selector = undefined;
		}
		if ( fn === false ) {
			fn = returnFalse;
		}
		return this.each(function() {
			jQuery.event.remove( this, types, fn, selector );
		});
	},

	bind: function( types, data, fn ) {
		return this.on( types, null, data, fn );
	},
	unbind: function( types, fn ) {
		return this.off( types, null, fn );
	},

	live: function( types, data, fn ) {
		jQuery( this.context ).on( types, this.selector, data, fn );
		return this;
	},
	die: function( types, fn ) {
		jQuery( this.context ).off( types, this.selector || "**", fn );
		return this;
	},

	delegate: function( selector, types, data, fn ) {
		return this.on( types, selector, data, fn );
	},
	undelegate: function( selector, types, fn ) {
		// ( namespace ) or ( selector, types [, fn] )
		return arguments.length == 1? this.off( selector, "**" ) : this.off( types, selector, fn );
	},

	trigger: function( type, data ) {
		return this.each(function() {
			jQuery.event.trigger( type, data, this );
		});
	},
	triggerHandler: function( type, data ) {
		if ( this[0] ) {
			return jQuery.event.trigger( type, data, this[0], true );
		}
	},

	toggle: function( fn ) {
		// Save reference to arguments for access in closure
		var args = arguments,
			guid = fn.guid || jQuery.guid++,
			i = 0,
			toggler = function( event ) {
				// Figure out which function to execute
				var lastToggle = ( jQuery._data( this, "lastToggle" + fn.guid ) || 0 ) % i;
				jQuery._data( this, "lastToggle" + fn.guid, lastToggle + 1 );

				// Make sure that clicks stop
				event.preventDefault();

				// and execute the function
				return args[ lastToggle ].apply( this, arguments ) || false;
			};

		// link all the functions, so any of them can unbind this click handler
		toggler.guid = guid;
		while ( i < args.length ) {
			args[ i++ ].guid = guid;
		}

		return this.click( toggler );
	},

	hover: function( fnOver, fnOut ) {
		return this.mouseenter( fnOver ).mouseleave( fnOut || fnOver );
	}
});

jQuery.each( ("blur focus focusin focusout load resize scroll unload click dblclick " +
	"mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave " +
	"change select submit keydown keypress keyup error contextmenu").split(" "), function( i, name ) {

	// Handle event binding
	jQuery.fn[ name ] = function( data, fn ) {
		if ( fn == null ) {
			fn = data;
			data = null;
		}

		return arguments.length > 0 ?
			this.on( name, null, data, fn ) :
			this.trigger( name );
	};

	if ( jQuery.attrFn ) {
		jQuery.attrFn[ name ] = true;
	}

	if ( rkeyEvent.test( name ) ) {
		jQuery.event.fixHooks[ name ] = jQuery.event.keyHooks;
	}

	if ( rmouseEvent.test( name ) ) {
		jQuery.event.fixHooks[ name ] = jQuery.event.mouseHooks;
	}
});



/*!
 * Sizzle CSS Selector Engine
 *  Copyright 2011, The Dojo Foundation
 *  Released under the MIT, BSD, and GPL Licenses.
 *  More information: http://sizzlejs.com/
 */
(function(){

var chunker = /((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^\[\]]*\]|['"][^'"]*['"]|[^\[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?((?:.|\r|\n)*)/g,
	expando = "sizcache" + (Math.random() + '').replace('.', ''),
	done = 0,
	toString = Object.prototype.toString,
	hasDuplicate = false,
	baseHasDuplicate = true,
	rBackslash = /\\/g,
	rReturn = /\r\n/g,
	rNonWord = /\W/;

// Here we check if the JavaScript engine is using some sort of
// optimization where it does not always call our comparision
// function. If that is the case, discard the hasDuplicate value.
//   Thus far that includes Google Chrome.
[0, 0].sort(function() {
	baseHasDuplicate = false;
	return 0;
});

var Sizzle = function( selector, context, results, seed ) {
	results = results || [];
	context = context || document;

	var origContext = context;

	if ( context.nodeType !== 1 && context.nodeType !== 9 ) {
		return [];
	}

	if ( !selector || typeof selector !== "string" ) {
		return results;
	}

	var m, set, checkSet, extra, ret, cur, pop, i,
		prune = true,
		contextXML = Sizzle.isXML( context ),
		parts = [],
		soFar = selector;

	// Reset the position of the chunker regexp (start from head)
	do {
		chunker.exec( "" );
		m = chunker.exec( soFar );

		if ( m ) {
			soFar = m[3];

			parts.push( m[1] );

			if ( m[2] ) {
				extra = m[3];
				break;
			}
		}
	} while ( m );

	if ( parts.length > 1 && origPOS.exec( selector ) ) {

		if ( parts.length === 2 && Expr.relative[ parts[0] ] ) {
			set = posProcess( parts[0] + parts[1], context, seed );

		} else {
			set = Expr.relative[ parts[0] ] ?
				[ context ] :
				Sizzle( parts.shift(), context );

			while ( parts.length ) {
				selector = parts.shift();

				if ( Expr.relative[ selector ] ) {
					selector += parts.shift();
				}

				set = posProcess( selector, set, seed );
			}
		}

	} else {
		// Take a shortcut and set the context if the root selector is an ID
		// (but not if it'll be faster if the inner selector is an ID)
		if ( !seed && parts.length > 1 && context.nodeType === 9 && !contextXML &&
				Expr.match.ID.test(parts[0]) && !Expr.match.ID.test(parts[parts.length - 1]) ) {

			ret = Sizzle.find( parts.shift(), context, contextXML );
			context = ret.expr ?
				Sizzle.filter( ret.expr, ret.set )[0] :
				ret.set[0];
		}

		if ( context ) {
			ret = seed ?
				{ expr: parts.pop(), set: makeArray(seed) } :
				Sizzle.find( parts.pop(), parts.length === 1 && (parts[0] === "~" || parts[0] === "+") && context.parentNode ? context.parentNode : context, contextXML );

			set = ret.expr ?
				Sizzle.filter( ret.expr, ret.set ) :
				ret.set;

			if ( parts.length > 0 ) {
				checkSet = makeArray( set );

			} else {
				prune = false;
			}

			while ( parts.length ) {
				cur = parts.pop();
				pop = cur;

				if ( !Expr.relative[ cur ] ) {
					cur = "";
				} else {
					pop = parts.pop();
				}

				if ( pop == null ) {
					pop = context;
				}

				Expr.relative[ cur ]( checkSet, pop, contextXML );
			}

		} else {
			checkSet = parts = [];
		}
	}

	if ( !checkSet ) {
		checkSet = set;
	}

	if ( !checkSet ) {
		Sizzle.error( cur || selector );
	}

	if ( toString.call(checkSet) === "[object Array]" ) {
		if ( !prune ) {
			results.push.apply( results, checkSet );

		} else if ( context && context.nodeType === 1 ) {
			for ( i = 0; checkSet[i] != null; i++ ) {
				if ( checkSet[i] && (checkSet[i] === true || checkSet[i].nodeType === 1 && Sizzle.contains(context, checkSet[i])) ) {
					results.push( set[i] );
				}
			}

		} else {
			for ( i = 0; checkSet[i] != null; i++ ) {
				if ( checkSet[i] && checkSet[i].nodeType === 1 ) {
					results.push( set[i] );
				}
			}
		}

	} else {
		makeArray( checkSet, results );
	}

	if ( extra ) {
		Sizzle( extra, origContext, results, seed );
		Sizzle.uniqueSort( results );
	}

	return results;
};

Sizzle.uniqueSort = function( results ) {
	if ( sortOrder ) {
		hasDuplicate = baseHasDuplicate;
		results.sort( sortOrder );

		if ( hasDuplicate ) {
			for ( var i = 1; i < results.length; i++ ) {
				if ( results[i] === results[ i - 1 ] ) {
					results.splice( i--, 1 );
				}
			}
		}
	}

	return results;
};

Sizzle.matches = function( expr, set ) {
	return Sizzle( expr, null, null, set );
};

Sizzle.matchesSelector = function( node, expr ) {
	return Sizzle( expr, null, null, [node] ).length > 0;
};

Sizzle.find = function( expr, context, isXML ) {
	var set, i, len, match, type, left;

	if ( !expr ) {
		return [];
	}

	for ( i = 0, len = Expr.order.length; i < len; i++ ) {
		type = Expr.order[i];

		if ( (match = Expr.leftMatch[ type ].exec( expr )) ) {
			left = match[1];
			match.splice( 1, 1 );

			if ( left.substr( left.length - 1 ) !== "\\" ) {
				match[1] = (match[1] || "").replace( rBackslash, "" );
				set = Expr.find[ type ]( match, context, isXML );

				if ( set != null ) {
					expr = expr.replace( Expr.match[ type ], "" );
					break;
				}
			}
		}
	}

	if ( !set ) {
		set = typeof context.getElementsByTagName !== "undefined" ?
			context.getElementsByTagName( "*" ) :
			[];
	}

	return { set: set, expr: expr };
};

Sizzle.filter = function( expr, set, inplace, not ) {
	var match, anyFound,
		type, found, item, filter, left,
		i, pass,
		old = expr,
		result = [],
		curLoop = set,
		isXMLFilter = set && set[0] && Sizzle.isXML( set[0] );

	while ( expr && set.length ) {
		for ( type in Expr.filter ) {
			if ( (match = Expr.leftMatch[ type ].exec( expr )) != null && match[2] ) {
				filter = Expr.filter[ type ];
				left = match[1];

				anyFound = false;

				match.splice(1,1);

				if ( left.substr( left.length - 1 ) === "\\" ) {
					continue;
				}

				if ( curLoop === result ) {
					result = [];
				}

				if ( Expr.preFilter[ type ] ) {
					match = Expr.preFilter[ type ]( match, curLoop, inplace, result, not, isXMLFilter );

					if ( !match ) {
						anyFound = found = true;

					} else if ( match === true ) {
						continue;
					}
				}

				if ( match ) {
					for ( i = 0; (item = curLoop[i]) != null; i++ ) {
						if ( item ) {
							found = filter( item, match, i, curLoop );
							pass = not ^ found;

							if ( inplace && found != null ) {
								if ( pass ) {
									anyFound = true;

								} else {
									curLoop[i] = false;
								}

							} else if ( pass ) {
								result.push( item );
								anyFound = true;
							}
						}
					}
				}

				if ( found !== undefined ) {
					if ( !inplace ) {
						curLoop = result;
					}

					expr = expr.replace( Expr.match[ type ], "" );

					if ( !anyFound ) {
						return [];
					}

					break;
				}
			}
		}

		// Improper expression
		if ( expr === old ) {
			if ( anyFound == null ) {
				Sizzle.error( expr );

			} else {
				break;
			}
		}

		old = expr;
	}

	return curLoop;
};

Sizzle.error = function( msg ) {
	throw new Error( "Syntax error, unrecognized expression: " + msg );
};

/**
 * Utility function for retreiving the text value of an array of DOM nodes
 * @param {Array|Element} elem
 */
var getText = Sizzle.getText = function( elem ) {
    var i, node,
		nodeType = elem.nodeType,
		ret = "";

	if ( nodeType ) {
		if ( nodeType === 1 || nodeType === 9 || nodeType === 11 ) {
			// Use textContent || innerText for elements
			if ( typeof elem.textContent === 'string' ) {
				return elem.textContent;
			} else if ( typeof elem.innerText === 'string' ) {
				// Replace IE's carriage returns
				return elem.innerText.replace( rReturn, '' );
			} else {
				// Traverse it's children
				for ( elem = elem.firstChild; elem; elem = elem.nextSibling) {
					ret += getText( elem );
				}
			}
		} else if ( nodeType === 3 || nodeType === 4 ) {
			return elem.nodeValue;
		}
	} else {

		// If no nodeType, this is expected to be an array
		for ( i = 0; (node = elem[i]); i++ ) {
			// Do not traverse comment nodes
			if ( node.nodeType !== 8 ) {
				ret += getText( node );
			}
		}
	}
	return ret;
};

var Expr = Sizzle.selectors = {
	order: [ "ID", "NAME", "TAG" ],

	match: {
		ID: /#((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,
		CLASS: /\.((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,
		NAME: /\[name=['"]*((?:[\w\u00c0-\uFFFF\-]|\\.)+)['"]*\]/,
		ATTR: /\[\s*((?:[\w\u00c0-\uFFFF\-]|\\.)+)\s*(?:(\S?=)\s*(?:(['"])(.*?)\3|(#?(?:[\w\u00c0-\uFFFF\-]|\\.)*)|)|)\s*\]/,
		TAG: /^((?:[\w\u00c0-\uFFFF\*\-]|\\.)+)/,
		CHILD: /:(only|nth|last|first)-child(?:\(\s*(even|odd|(?:[+\-]?\d+|(?:[+\-]?\d*)?n\s*(?:[+\-]\s*\d+)?))\s*\))?/,
		POS: /:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^\-]|$)/,
		PSEUDO: /:((?:[\w\u00c0-\uFFFF\-]|\\.)+)(?:\((['"]?)((?:\([^\)]+\)|[^\(\)]*)+)\2\))?/
	},

	leftMatch: {},

	attrMap: {
		"class": "className",
		"for": "htmlFor"
	},

	attrHandle: {
		href: function( elem ) {
			return elem.getAttribute( "href" );
		},
		type: function( elem ) {
			return elem.getAttribute( "type" );
		}
	},

	relative: {
		"+": function(checkSet, part){
			var isPartStr = typeof part === "string",
				isTag = isPartStr && !rNonWord.test( part ),
				isPartStrNotTag = isPartStr && !isTag;

			if ( isTag ) {
				part = part.toLowerCase();
			}

			for ( var i = 0, l = checkSet.length, elem; i < l; i++ ) {
				if ( (elem = checkSet[i]) ) {
					while ( (elem = elem.previousSibling) && elem.nodeType !== 1 ) {}

					checkSet[i] = isPartStrNotTag || elem && elem.nodeName.toLowerCase() === part ?
						elem || false :
						elem === part;
				}
			}

			if ( isPartStrNotTag ) {
				Sizzle.filter( part, checkSet, true );
			}
		},

		">": function( checkSet, part ) {
			var elem,
				isPartStr = typeof part === "string",
				i = 0,
				l = checkSet.length;

			if ( isPartStr && !rNonWord.test( part ) ) {
				part = part.toLowerCase();

				for ( ; i < l; i++ ) {
					elem = checkSet[i];

					if ( elem ) {
						var parent = elem.parentNode;
						checkSet[i] = parent.nodeName.toLowerCase() === part ? parent : false;
					}
				}

			} else {
				for ( ; i < l; i++ ) {
					elem = checkSet[i];

					if ( elem ) {
						checkSet[i] = isPartStr ?
							elem.parentNode :
							elem.parentNode === part;
					}
				}

				if ( isPartStr ) {
					Sizzle.filter( part, checkSet, true );
				}
			}
		},

		"": function(checkSet, part, isXML){
			var nodeCheck,
				doneName = done++,
				checkFn = dirCheck;

			if ( typeof part === "string" && !rNonWord.test( part ) ) {
				part = part.toLowerCase();
				nodeCheck = part;
				checkFn = dirNodeCheck;
			}

			checkFn( "parentNode", part, doneName, checkSet, nodeCheck, isXML );
		},

		"~": function( checkSet, part, isXML ) {
			var nodeCheck,
				doneName = done++,
				checkFn = dirCheck;

			if ( typeof part === "string" && !rNonWord.test( part ) ) {
				part = part.toLowerCase();
				nodeCheck = part;
				checkFn = dirNodeCheck;
			}

			checkFn( "previousSibling", part, doneName, checkSet, nodeCheck, isXML );
		}
	},

	find: {
		ID: function( match, context, isXML ) {
			if ( typeof context.getElementById !== "undefined" && !isXML ) {
				var m = context.getElementById(match[1]);
				// Check parentNode to catch when Blackberry 4.6 returns
				// nodes that are no longer in the document #6963
				return m && m.parentNode ? [m] : [];
			}
		},

		NAME: function( match, context ) {
			if ( typeof context.getElementsByName !== "undefined" ) {
				var ret = [],
					results = context.getElementsByName( match[1] );

				for ( var i = 0, l = results.length; i < l; i++ ) {
					if ( results[i].getAttribute("name") === match[1] ) {
						ret.push( results[i] );
					}
				}

				return ret.length === 0 ? null : ret;
			}
		},

		TAG: function( match, context ) {
			if ( typeof context.getElementsByTagName !== "undefined" ) {
				return context.getElementsByTagName( match[1] );
			}
		}
	},
	preFilter: {
		CLASS: function( match, curLoop, inplace, result, not, isXML ) {
			match = " " + match[1].replace( rBackslash, "" ) + " ";

			if ( isXML ) {
				return match;
			}

			for ( var i = 0, elem; (elem = curLoop[i]) != null; i++ ) {
				if ( elem ) {
					if ( not ^ (elem.className && (" " + elem.className + " ").replace(/[\t\n\r]/g, " ").indexOf(match) >= 0) ) {
						if ( !inplace ) {
							result.push( elem );
						}

					} else if ( inplace ) {
						curLoop[i] = false;
					}
				}
			}

			return false;
		},

		ID: function( match ) {
			return match[1].replace( rBackslash, "" );
		},

		TAG: function( match, curLoop ) {
			return match[1].replace( rBackslash, "" ).toLowerCase();
		},

		CHILD: function( match ) {
			if ( match[1] === "nth" ) {
				if ( !match[2] ) {
					Sizzle.error( match[0] );
				}

				match[2] = match[2].replace(/^\+|\s*/g, '');

				// parse equations like 'even', 'odd', '5', '2n', '3n+2', '4n-1', '-n+6'
				var test = /(-?)(\d*)(?:n([+\-]?\d*))?/.exec(
					match[2] === "even" && "2n" || match[2] === "odd" && "2n+1" ||
					!/\D/.test( match[2] ) && "0n+" + match[2] || match[2]);

				// calculate the numbers (first)n+(last) including if they are negative
				match[2] = (test[1] + (test[2] || 1)) - 0;
				match[3] = test[3] - 0;
			}
			else if ( match[2] ) {
				Sizzle.error( match[0] );
			}

			// TODO: Move to normal caching system
			match[0] = done++;

			return match;
		},

		ATTR: function( match, curLoop, inplace, result, not, isXML ) {
			var name = match[1] = match[1].replace( rBackslash, "" );

			if ( !isXML && Expr.attrMap[name] ) {
				match[1] = Expr.attrMap[name];
			}

			// Handle if an un-quoted value was used
			match[4] = ( match[4] || match[5] || "" ).replace( rBackslash, "" );

			if ( match[2] === "~=" ) {
				match[4] = " " + match[4] + " ";
			}

			return match;
		},

		PSEUDO: function( match, curLoop, inplace, result, not ) {
			if ( match[1] === "not" ) {
				// If we're dealing with a complex expression, or a simple one
				if ( ( chunker.exec(match[3]) || "" ).length > 1 || /^\w/.test(match[3]) ) {
					match[3] = Sizzle(match[3], null, null, curLoop);

				} else {
					var ret = Sizzle.filter(match[3], curLoop, inplace, true ^ not);

					if ( !inplace ) {
						result.push.apply( result, ret );
					}

					return false;
				}

			} else if ( Expr.match.POS.test( match[0] ) || Expr.match.CHILD.test( match[0] ) ) {
				return true;
			}

			return match;
		},

		POS: function( match ) {
			match.unshift( true );

			return match;
		}
	},

	filters: {
		enabled: function( elem ) {
			return elem.disabled === false && elem.type !== "hidden";
		},

		disabled: function( elem ) {
			return elem.disabled === true;
		},

		checked: function( elem ) {
			return elem.checked === true;
		},

		selected: function( elem ) {
			// Accessing this property makes selected-by-default
			// options in Safari work properly
			if ( elem.parentNode ) {
				elem.parentNode.selectedIndex;
			}

			return elem.selected === true;
		},

		parent: function( elem ) {
			return !!elem.firstChild;
		},

		empty: function( elem ) {
			return !elem.firstChild;
		},

		has: function( elem, i, match ) {
			return !!Sizzle( match[3], elem ).length;
		},

		header: function( elem ) {
			return (/h\d/i).test( elem.nodeName );
		},

		text: function( elem ) {
			var attr = elem.getAttribute( "type" ), type = elem.type;
			// IE6 and 7 will map elem.type to 'text' for new HTML5 types (search, etc)
			// use getAttribute instead to test this case
			return elem.nodeName.toLowerCase() === "input" && "text" === type && ( attr === type || attr === null );
		},

		radio: function( elem ) {
			return elem.nodeName.toLowerCase() === "input" && "radio" === elem.type;
		},

		checkbox: function( elem ) {
			return elem.nodeName.toLowerCase() === "input" && "checkbox" === elem.type;
		},

		file: function( elem ) {
			return elem.nodeName.toLowerCase() === "input" && "file" === elem.type;
		},

		password: function( elem ) {
			return elem.nodeName.toLowerCase() === "input" && "password" === elem.type;
		},

		submit: function( elem ) {
			var name = elem.nodeName.toLowerCase();
			return (name === "input" || name === "button") && "submit" === elem.type;
		},

		image: function( elem ) {
			return elem.nodeName.toLowerCase() === "input" && "image" === elem.type;
		},

		reset: function( elem ) {
			var name = elem.nodeName.toLowerCase();
			return (name === "input" || name === "button") && "reset" === elem.type;
		},

		button: function( elem ) {
			var name = elem.nodeName.toLowerCase();
			return name === "input" && "button" === elem.type || name === "button";
		},

		input: function( elem ) {
			return (/input|select|textarea|button/i).test( elem.nodeName );
		},

		focus: function( elem ) {
			return elem === elem.ownerDocument.activeElement;
		}
	},
	setFilters: {
		first: function( elem, i ) {
			return i === 0;
		},

		last: function( elem, i, match, array ) {
			return i === array.length - 1;
		},

		even: function( elem, i ) {
			return i % 2 === 0;
		},

		odd: function( elem, i ) {
			return i % 2 === 1;
		},

		lt: function( elem, i, match ) {
			return i < match[3] - 0;
		},

		gt: function( elem, i, match ) {
			return i > match[3] - 0;
		},

		nth: function( elem, i, match ) {
			return match[3] - 0 === i;
		},

		eq: function( elem, i, match ) {
			return match[3] - 0 === i;
		}
	},
	filter: {
		PSEUDO: function( elem, match, i, array ) {
			var name = match[1],
				filter = Expr.filters[ name ];

			if ( filter ) {
				return filter( elem, i, match, array );

			} else if ( name === "contains" ) {
				return (elem.textContent || elem.innerText || getText([ elem ]) || "").indexOf(match[3]) >= 0;

			} else if ( name === "not" ) {
				var not = match[3];

				for ( var j = 0, l = not.length; j < l; j++ ) {
					if ( not[j] === elem ) {
						return false;
					}
				}

				return true;

			} else {
				Sizzle.error( name );
			}
		},

		CHILD: function( elem, match ) {
			var first, last,
				doneName, parent, cache,
				count, diff,
				type = match[1],
				node = elem;

			switch ( type ) {
				case "only":
				case "first":
					while ( (node = node.previousSibling) ) {
						if ( node.nodeType === 1 ) {
							return false;
						}
					}

					if ( type === "first" ) {
						return true;
					}

					node = elem;

					/* falls through */
				case "last":
					while ( (node = node.nextSibling) ) {
						if ( node.nodeType === 1 ) {
							return false;
						}
					}

					return true;

				case "nth":
					first = match[2];
					last = match[3];

					if ( first === 1 && last === 0 ) {
						return true;
					}

					doneName = match[0];
					parent = elem.parentNode;

					if ( parent && (parent[ expando ] !== doneName || !elem.nodeIndex) ) {
						count = 0;

						for ( node = parent.firstChild; node; node = node.nextSibling ) {
							if ( node.nodeType === 1 ) {
								node.nodeIndex = ++count;
							}
						}

						parent[ expando ] = doneName;
					}

					diff = elem.nodeIndex - last;

					if ( first === 0 ) {
						return diff === 0;

					} else {
						return ( diff % first === 0 && diff / first >= 0 );
					}
			}
		},

		ID: function( elem, match ) {
			return elem.nodeType === 1 && elem.getAttribute("id") === match;
		},

		TAG: function( elem, match ) {
			return (match === "*" && elem.nodeType === 1) || !!elem.nodeName && elem.nodeName.toLowerCase() === match;
		},

		CLASS: function( elem, match ) {
			return (" " + (elem.className || elem.getAttribute("class")) + " ")
				.indexOf( match ) > -1;
		},

		ATTR: function( elem, match ) {
			var name = match[1],
				result = Sizzle.attr ?
					Sizzle.attr( elem, name ) :
					Expr.attrHandle[ name ] ?
					Expr.attrHandle[ name ]( elem ) :
					elem[ name ] != null ?
						elem[ name ] :
						elem.getAttribute( name ),
				value = result + "",
				type = match[2],
				check = match[4];

			return result == null ?
				type === "!=" :
				!type && Sizzle.attr ?
				result != null :
				type === "=" ?
				value === check :
				type === "*=" ?
				value.indexOf(check) >= 0 :
				type === "~=" ?
				(" " + value + " ").indexOf(check) >= 0 :
				!check ?
				value && result !== false :
				type === "!=" ?
				value !== check :
				type === "^=" ?
				value.indexOf(check) === 0 :
				type === "$=" ?
				value.substr(value.length - check.length) === check :
				type === "|=" ?
				value === check || value.substr(0, check.length + 1) === check + "-" :
				false;
		},

		POS: function( elem, match, i, array ) {
			var name = match[2],
				filter = Expr.setFilters[ name ];

			if ( filter ) {
				return filter( elem, i, match, array );
			}
		}
	}
};

var origPOS = Expr.match.POS,
	fescape = function(all, num){
		return "\\" + (num - 0 + 1);
	};

for ( var type in Expr.match ) {
	Expr.match[ type ] = new RegExp( Expr.match[ type ].source + (/(?![^\[]*\])(?![^\(]*\))/.source) );
	Expr.leftMatch[ type ] = new RegExp( /(^(?:.|\r|\n)*?)/.source + Expr.match[ type ].source.replace(/\\(\d+)/g, fescape) );
}
// Expose origPOS
// "global" as in regardless of relation to brackets/parens
Expr.match.globalPOS = origPOS;

var makeArray = function( array, results ) {
	array = Array.prototype.slice.call( array, 0 );

	if ( results ) {
		results.push.apply( results, array );
		return results;
	}

	return array;
};

// Perform a simple check to determine if the browser is capable of
// converting a NodeList to an array using builtin methods.
// Also verifies that the returned array holds DOM nodes
// (which is not the case in the Blackberry browser)
try {
	Array.prototype.slice.call( document.documentElement.childNodes, 0 )[0].nodeType;

// Provide a fallback method if it does not work
} catch( e ) {
	makeArray = function( array, results ) {
		var i = 0,
			ret = results || [];

		if ( toString.call(array) === "[object Array]" ) {
			Array.prototype.push.apply( ret, array );

		} else {
			if ( typeof array.length === "number" ) {
				for ( var l = array.length; i < l; i++ ) {
					ret.push( array[i] );
				}

			} else {
				for ( ; array[i]; i++ ) {
					ret.push( array[i] );
				}
			}
		}

		return ret;
	};
}

var sortOrder, siblingCheck;

if ( document.documentElement.compareDocumentPosition ) {
	sortOrder = function( a, b ) {
		if ( a === b ) {
			hasDuplicate = true;
			return 0;
		}

		if ( !a.compareDocumentPosition || !b.compareDocumentPosition ) {
			return a.compareDocumentPosition ? -1 : 1;
		}

		return a.compareDocumentPosition(b) & 4 ? -1 : 1;
	};

} else {
	sortOrder = function( a, b ) {
		// The nodes are identical, we can exit early
		if ( a === b ) {
			hasDuplicate = true;
			return 0;

		// Fallback to using sourceIndex (in IE) if it's available on both nodes
		} else if ( a.sourceIndex && b.sourceIndex ) {
			return a.sourceIndex - b.sourceIndex;
		}

		var al, bl,
			ap = [],
			bp = [],
			aup = a.parentNode,
			bup = b.parentNode,
			cur = aup;

		// If the nodes are siblings (or identical) we can do a quick check
		if ( aup === bup ) {
			return siblingCheck( a, b );

		// If no parents were found then the nodes are disconnected
		} else if ( !aup ) {
			return -1;

		} else if ( !bup ) {
			return 1;
		}

		// Otherwise they're somewhere else in the tree so we need
		// to build up a full list of the parentNodes for comparison
		while ( cur ) {
			ap.unshift( cur );
			cur = cur.parentNode;
		}

		cur = bup;

		while ( cur ) {
			bp.unshift( cur );
			cur = cur.parentNode;
		}

		al = ap.length;
		bl = bp.length;

		// Start walking down the tree looking for a discrepancy
		for ( var i = 0; i < al && i < bl; i++ ) {
			if ( ap[i] !== bp[i] ) {
				return siblingCheck( ap[i], bp[i] );
			}
		}

		// We ended someplace up the tree so do a sibling check
		return i === al ?
			siblingCheck( a, bp[i], -1 ) :
			siblingCheck( ap[i], b, 1 );
	};

	siblingCheck = function( a, b, ret ) {
		if ( a === b ) {
			return ret;
		}

		var cur = a.nextSibling;

		while ( cur ) {
			if ( cur === b ) {
				return -1;
			}

			cur = cur.nextSibling;
		}

		return 1;
	};
}

// Check to see if the browser returns elements by name when
// querying by getElementById (and provide a workaround)
(function(){
	// We're going to inject a fake input element with a specified name
	var form = document.createElement("div"),
		id = "script" + (new Date()).getTime(),
		root = document.documentElement;

	form.innerHTML = "<a name='" + id + "'/>";

	// Inject it into the root element, check its status, and remove it quickly
	root.insertBefore( form, root.firstChild );

	// The workaround has to do additional checks after a getElementById
	// Which slows things down for other browsers (hence the branching)
	if ( document.getElementById( id ) ) {
		Expr.find.ID = function( match, context, isXML ) {
			if ( typeof context.getElementById !== "undefined" && !isXML ) {
				var m = context.getElementById(match[1]);

				return m ?
					m.id === match[1] || typeof m.getAttributeNode !== "undefined" && m.getAttributeNode("id").nodeValue === match[1] ?
						[m] :
						undefined :
					[];
			}
		};

		Expr.filter.ID = function( elem, match ) {
			var node = typeof elem.getAttributeNode !== "undefined" && elem.getAttributeNode("id");

			return elem.nodeType === 1 && node && node.nodeValue === match;
		};
	}

	root.removeChild( form );

	// release memory in IE
	root = form = null;
})();

(function(){
	// Check to see if the browser returns only elements
	// when doing getElementsByTagName("*")

	// Create a fake element
	var div = document.createElement("div");
	div.appendChild( document.createComment("") );

	// Make sure no comments are found
	if ( div.getElementsByTagName("*").length > 0 ) {
		Expr.find.TAG = function( match, context ) {
			var results = context.getElementsByTagName( match[1] );

			// Filter out possible comments
			if ( match[1] === "*" ) {
				var tmp = [];

				for ( var i = 0; results[i]; i++ ) {
					if ( results[i].nodeType === 1 ) {
						tmp.push( results[i] );
					}
				}

				results = tmp;
			}

			return results;
		};
	}

	// Check to see if an attribute returns normalized href attributes
	div.innerHTML = "<a href='#'></a>";

	if ( div.firstChild && typeof div.firstChild.getAttribute !== "undefined" &&
			div.firstChild.getAttribute("href") !== "#" ) {

		Expr.attrHandle.href = function( elem ) {
			return elem.getAttribute( "href", 2 );
		};
	}

	// release memory in IE
	div = null;
})();

if ( document.querySelectorAll ) {
	(function(){
		var oldSizzle = Sizzle,
			div = document.createElement("div"),
			id = "__sizzle__";

		div.innerHTML = "<p class='TEST'></p>";

		// Safari can't handle uppercase or unicode characters when
		// in quirks mode.
		if ( div.querySelectorAll && div.querySelectorAll(".TEST").length === 0 ) {
			return;
		}

		Sizzle = function( query, context, extra, seed ) {
			context = context || document;

			// Only use querySelectorAll on non-XML documents
			// (ID selectors don't work in non-HTML documents)
			if ( !seed && !Sizzle.isXML(context) ) {
				// See if we find a selector to speed up
				var match = /^(\w+$)|^\.([\w\-]+$)|^#([\w\-]+$)/.exec( query );

				if ( match && (context.nodeType === 1 || context.nodeType === 9) ) {
					// Speed-up: Sizzle("TAG")
					if ( match[1] ) {
						return makeArray( context.getElementsByTagName( query ), extra );

					// Speed-up: Sizzle(".CLASS")
					} else if ( match[2] && Expr.find.CLASS && context.getElementsByClassName ) {
						return makeArray( context.getElementsByClassName( match[2] ), extra );
					}
				}

				if ( context.nodeType === 9 ) {
					// Speed-up: Sizzle("body")
					// The body element only exists once, optimize finding it
					if ( query === "body" && context.body ) {
						return makeArray( [ context.body ], extra );

					// Speed-up: Sizzle("#ID")
					} else if ( match && match[3] ) {
						var elem = context.getElementById( match[3] );

						// Check parentNode to catch when Blackberry 4.6 returns
						// nodes that are no longer in the document #6963
						if ( elem && elem.parentNode ) {
							// Handle the case where IE and Opera return items
							// by name instead of ID
							if ( elem.id === match[3] ) {
								return makeArray( [ elem ], extra );
							}

						} else {
							return makeArray( [], extra );
						}
					}

					try {
						return makeArray( context.querySelectorAll(query), extra );
					} catch(qsaError) {}

				// qSA works strangely on Element-rooted queries
				// We can work around this by specifying an extra ID on the root
				// and working up from there (Thanks to Andrew Dupont for the technique)
				// IE 8 doesn't work on object elements
				} else if ( context.nodeType === 1 && context.nodeName.toLowerCase() !== "object" ) {
					var oldContext = context,
						old = context.getAttribute( "id" ),
						nid = old || id,
						hasParent = context.parentNode,
						relativeHierarchySelector = /^\s*[+~]/.test( query );

					if ( !old ) {
						context.setAttribute( "id", nid );
					} else {
						nid = nid.replace( /'/g, "\\$&" );
					}
					if ( relativeHierarchySelector && hasParent ) {
						context = context.parentNode;
					}

					try {
						if ( !relativeHierarchySelector || hasParent ) {
							return makeArray( context.querySelectorAll( "[id='" + nid + "'] " + query ), extra );
						}

					} catch(pseudoError) {
					} finally {
						if ( !old ) {
							oldContext.removeAttribute( "id" );
						}
					}
				}
			}

			return oldSizzle(query, context, extra, seed);
		};

		for ( var prop in oldSizzle ) {
			Sizzle[ prop ] = oldSizzle[ prop ];
		}

		// release memory in IE
		div = null;
	})();
}

(function(){
	var html = document.documentElement,
		matches = html.matchesSelector || html.mozMatchesSelector || html.webkitMatchesSelector || html.msMatchesSelector;

	if ( matches ) {
		// Check to see if it's possible to do matchesSelector
		// on a disconnected node (IE 9 fails this)
		var disconnectedMatch = !matches.call( document.createElement( "div" ), "div" ),
			pseudoWorks = false;

		try {
			// This should fail with an exception
			// Gecko does not error, returns false instead
			matches.call( document.documentElement, "[test!='']:sizzle" );

		} catch( pseudoError ) {
			pseudoWorks = true;
		}

		Sizzle.matchesSelector = function( node, expr ) {
			// Make sure that attribute selectors are quoted
			expr = expr.replace(/\=\s*([^'"\]]*)\s*\]/g, "='$1']");

			if ( !Sizzle.isXML( node ) ) {
				try {
					if ( pseudoWorks || !Expr.match.PSEUDO.test( expr ) && !/!=/.test( expr ) ) {
						var ret = matches.call( node, expr );

						// IE 9's matchesSelector returns false on disconnected nodes
						if ( ret || !disconnectedMatch ||
								// As well, disconnected nodes are said to be in a document
								// fragment in IE 9, so check for that
								node.document && node.document.nodeType !== 11 ) {
							return ret;
						}
					}
				} catch(e) {}
			}

			return Sizzle(expr, null, null, [node]).length > 0;
		};
	}
})();

(function(){
	var div = document.createElement("div");

	div.innerHTML = "<div class='test e'></div><div class='test'></div>";

	// Opera can't find a second classname (in 9.6)
	// Also, make sure that getElementsByClassName actually exists
	if ( !div.getElementsByClassName || div.getElementsByClassName("e").length === 0 ) {
		return;
	}

	// Safari caches class attributes, doesn't catch changes (in 3.2)
	div.lastChild.className = "e";

	if ( div.getElementsByClassName("e").length === 1 ) {
		return;
	}

	Expr.order.splice(1, 0, "CLASS");
	Expr.find.CLASS = function( match, context, isXML ) {
		if ( typeof context.getElementsByClassName !== "undefined" && !isXML ) {
			return context.getElementsByClassName(match[1]);
		}
	};

	// release memory in IE
	div = null;
})();

function dirNodeCheck( dir, cur, doneName, checkSet, nodeCheck, isXML ) {
	for ( var i = 0, l = checkSet.length; i < l; i++ ) {
		var elem = checkSet[i];

		if ( elem ) {
			var match = false;

			elem = elem[dir];

			while ( elem ) {
				if ( elem[ expando ] === doneName ) {
					match = checkSet[elem.sizset];
					break;
				}

				if ( elem.nodeType === 1 && !isXML ){
					elem[ expando ] = doneName;
					elem.sizset = i;
				}

				if ( elem.nodeName.toLowerCase() === cur ) {
					match = elem;
					break;
				}

				elem = elem[dir];
			}

			checkSet[i] = match;
		}
	}
}

function dirCheck( dir, cur, doneName, checkSet, nodeCheck, isXML ) {
	for ( var i = 0, l = checkSet.length; i < l; i++ ) {
		var elem = checkSet[i];

		if ( elem ) {
			var match = false;

			elem = elem[dir];

			while ( elem ) {
				if ( elem[ expando ] === doneName ) {
					match = checkSet[elem.sizset];
					break;
				}

				if ( elem.nodeType === 1 ) {
					if ( !isXML ) {
						elem[ expando ] = doneName;
						elem.sizset = i;
					}

					if ( typeof cur !== "string" ) {
						if ( elem === cur ) {
							match = true;
							break;
						}

					} else if ( Sizzle.filter( cur, [elem] ).length > 0 ) {
						match = elem;
						break;
					}
				}

				elem = elem[dir];
			}

			checkSet[i] = match;
		}
	}
}

if ( document.documentElement.contains ) {
	Sizzle.contains = function( a, b ) {
		return a !== b && (a.contains ? a.contains(b) : true);
	};

} else if ( document.documentElement.compareDocumentPosition ) {
	Sizzle.contains = function( a, b ) {
		return !!(a.compareDocumentPosition(b) & 16);
	};

} else {
	Sizzle.contains = function() {
		return false;
	};
}

Sizzle.isXML = function( elem ) {
	// documentElement is verified for cases where it doesn't yet exist
	// (such as loading iframes in IE - #4833)
	var documentElement = (elem ? elem.ownerDocument || elem : 0).documentElement;

	return documentElement ? documentElement.nodeName !== "HTML" : false;
};

var posProcess = function( selector, context, seed ) {
	var match,
		tmpSet = [],
		later = "",
		root = context.nodeType ? [context] : context;

	// Position selectors must be done after the filter
	// And so must :not(positional) so we move all PSEUDOs to the end
	while ( (match = Expr.match.PSEUDO.exec( selector )) ) {
		later += match[0];
		selector = selector.replace( Expr.match.PSEUDO, "" );
	}

	selector = Expr.relative[selector] ? selector + "*" : selector;

	for ( var i = 0, l = root.length; i < l; i++ ) {
		Sizzle( selector, root[i], tmpSet, seed );
	}

	return Sizzle.filter( later, tmpSet );
};

// EXPOSE
// Override sizzle attribute retrieval
Sizzle.attr = jQuery.attr;
Sizzle.selectors.attrMap = {};
jQuery.find = Sizzle;
jQuery.expr = Sizzle.selectors;
jQuery.expr[":"] = jQuery.expr.filters;
jQuery.unique = Sizzle.uniqueSort;
jQuery.text = Sizzle.getText;
jQuery.isXMLDoc = Sizzle.isXML;
jQuery.contains = Sizzle.contains;


})();


var runtil = /Until$/,
	rparentsprev = /^(?:parents|prevUntil|prevAll)/,
	// Note: This RegExp should be improved, or likely pulled from Sizzle
	rmultiselector = /,/,
	isSimple = /^.[^:#\[\.,]*$/,
	slice = Array.prototype.slice,
	POS = jQuery.expr.match.globalPOS,
	// methods guaranteed to produce a unique set when starting from a unique set
	guaranteedUnique = {
		children: true,
		contents: true,
		next: true,
		prev: true
	};

jQuery.fn.extend({
	find: function( selector ) {
		var self = this,
			i, l;

		if ( typeof selector !== "string" ) {
			return jQuery( selector ).filter(function() {
				for ( i = 0, l = self.length; i < l; i++ ) {
					if ( jQuery.contains( self[ i ], this ) ) {
						return true;
					}
				}
			});
		}

		var ret = this.pushStack( "", "find", selector ),
			length, n, r;

		for ( i = 0, l = this.length; i < l; i++ ) {
			length = ret.length;
			jQuery.find( selector, this[i], ret );

			if ( i > 0 ) {
				// Make sure that the results are unique
				for ( n = length; n < ret.length; n++ ) {
					for ( r = 0; r < length; r++ ) {
						if ( ret[r] === ret[n] ) {
							ret.splice(n--, 1);
							break;
						}
					}
				}
			}
		}

		return ret;
	},

	has: function( target ) {
		var targets = jQuery( target );
		return this.filter(function() {
			for ( var i = 0, l = targets.length; i < l; i++ ) {
				if ( jQuery.contains( this, targets[i] ) ) {
					return true;
				}
			}
		});
	},

	not: function( selector ) {
		return this.pushStack( winnow(this, selector, false), "not", selector);
	},

	filter: function( selector ) {
		return this.pushStack( winnow(this, selector, true), "filter", selector );
	},

	is: function( selector ) {
		return !!selector && (
			typeof selector === "string" ?
				// If this is a positional selector, check membership in the returned set
				// so $("p:first").is("p:last") won't return true for a doc with two "p".
				POS.test( selector ) ?
					jQuery( selector, this.context ).index( this[0] ) >= 0 :
					jQuery.filter( selector, this ).length > 0 :
				this.filter( selector ).length > 0 );
	},

	closest: function( selectors, context ) {
		var ret = [], i, l, cur = this[0];

		// Array (deprecated as of jQuery 1.7)
		if ( jQuery.isArray( selectors ) ) {
			var level = 1;

			while ( cur && cur.ownerDocument && cur !== context ) {
				for ( i = 0; i < selectors.length; i++ ) {

					if ( jQuery( cur ).is( selectors[ i ] ) ) {
						ret.push({ selector: selectors[ i ], elem: cur, level: level });
					}
				}

				cur = cur.parentNode;
				level++;
			}

			return ret;
		}

		// String
		var pos = POS.test( selectors ) || typeof selectors !== "string" ?
				jQuery( selectors, context || this.context ) :
				0;

		for ( i = 0, l = this.length; i < l; i++ ) {
			cur = this[i];

			while ( cur ) {
				if ( pos ? pos.index(cur) > -1 : jQuery.find.matchesSelector(cur, selectors) ) {
					ret.push( cur );
					break;

				} else {
					cur = cur.parentNode;
					if ( !cur || !cur.ownerDocument || cur === context || cur.nodeType === 11 ) {
						break;
					}
				}
			}
		}

		ret = ret.length > 1 ? jQuery.unique( ret ) : ret;

		return this.pushStack( ret, "closest", selectors );
	},

	// Determine the position of an element within
	// the matched set of elements
	index: function( elem ) {

		// No argument, return index in parent
		if ( !elem ) {
			return ( this[0] && this[0].parentNode ) ? this.prevAll().length : -1;
		}

		// index in selector
		if ( typeof elem === "string" ) {
			return jQuery.inArray( this[0], jQuery( elem ) );
		}

		// Locate the position of the desired element
		return jQuery.inArray(
			// If it receives a jQuery object, the first element is used
			elem.jquery ? elem[0] : elem, this );
	},

	add: function( selector, context ) {
		var set = typeof selector === "string" ?
				jQuery( selector, context ) :
				jQuery.makeArray( selector && selector.nodeType ? [ selector ] : selector ),
			all = jQuery.merge( this.get(), set );

		return this.pushStack( isDisconnected( set[0] ) || isDisconnected( all[0] ) ?
			all :
			jQuery.unique( all ) );
	},

	andSelf: function() {
		return this.add( this.prevObject );
	}
});

// A painfully simple check to see if an element is disconnected
// from a document (should be improved, where feasible).
function isDisconnected( node ) {
	return !node || !node.parentNode || node.parentNode.nodeType === 11;
}

jQuery.each({
	parent: function( elem ) {
		var parent = elem.parentNode;
		return parent && parent.nodeType !== 11 ? parent : null;
	},
	parents: function( elem ) {
		return jQuery.dir( elem, "parentNode" );
	},
	parentsUntil: function( elem, i, until ) {
		return jQuery.dir( elem, "parentNode", until );
	},
	next: function( elem ) {
		return jQuery.nth( elem, 2, "nextSibling" );
	},
	prev: function( elem ) {
		return jQuery.nth( elem, 2, "previousSibling" );
	},
	nextAll: function( elem ) {
		return jQuery.dir( elem, "nextSibling" );
	},
	prevAll: function( elem ) {
		return jQuery.dir( elem, "previousSibling" );
	},
	nextUntil: function( elem, i, until ) {
		return jQuery.dir( elem, "nextSibling", until );
	},
	prevUntil: function( elem, i, until ) {
		return jQuery.dir( elem, "previousSibling", until );
	},
	siblings: function( elem ) {
		return jQuery.sibling( ( elem.parentNode || {} ).firstChild, elem );
	},
	children: function( elem ) {
		return jQuery.sibling( elem.firstChild );
	},
	contents: function( elem ) {
		return jQuery.nodeName( elem, "iframe" ) ?
			elem.contentDocument || elem.contentWindow.document :
			jQuery.makeArray( elem.childNodes );
	}
}, function( name, fn ) {
	jQuery.fn[ name ] = function( until, selector ) {
		var ret = jQuery.map( this, fn, until );

		if ( !runtil.test( name ) ) {
			selector = until;
		}

		if ( selector && typeof selector === "string" ) {
			ret = jQuery.filter( selector, ret );
		}

		ret = this.length > 1 && !guaranteedUnique[ name ] ? jQuery.unique( ret ) : ret;

		if ( (this.length > 1 || rmultiselector.test( selector )) && rparentsprev.test( name ) ) {
			ret = ret.reverse();
		}

		return this.pushStack( ret, name, slice.call( arguments ).join(",") );
	};
});

jQuery.extend({
	filter: function( expr, elems, not ) {
		if ( not ) {
			expr = ":not(" + expr + ")";
		}

		return elems.length === 1 ?
			jQuery.find.matchesSelector(elems[0], expr) ? [ elems[0] ] : [] :
			jQuery.find.matches(expr, elems);
	},

	dir: function( elem, dir, until ) {
		var matched = [],
			cur = elem[ dir ];

		while ( cur && cur.nodeType !== 9 && (until === undefined || cur.nodeType !== 1 || !jQuery( cur ).is( until )) ) {
			if ( cur.nodeType === 1 ) {
				matched.push( cur );
			}
			cur = cur[dir];
		}
		return matched;
	},

	nth: function( cur, result, dir, elem ) {
		result = result || 1;
		var num = 0;

		for ( ; cur; cur = cur[dir] ) {
			if ( cur.nodeType === 1 && ++num === result ) {
				break;
			}
		}

		return cur;
	},

	sibling: function( n, elem ) {
		var r = [];

		for ( ; n; n = n.nextSibling ) {
			if ( n.nodeType === 1 && n !== elem ) {
				r.push( n );
			}
		}

		return r;
	}
});

// Implement the identical functionality for filter and not
function winnow( elements, qualifier, keep ) {

	// Can't pass null or undefined to indexOf in Firefox 4
	// Set to 0 to skip string check
	qualifier = qualifier || 0;

	if ( jQuery.isFunction( qualifier ) ) {
		return jQuery.grep(elements, function( elem, i ) {
			var retVal = !!qualifier.call( elem, i, elem );
			return retVal === keep;
		});

	} else if ( qualifier.nodeType ) {
		return jQuery.grep(elements, function( elem, i ) {
			return ( elem === qualifier ) === keep;
		});

	} else if ( typeof qualifier === "string" ) {
		var filtered = jQuery.grep(elements, function( elem ) {
			return elem.nodeType === 1;
		});

		if ( isSimple.test( qualifier ) ) {
			return jQuery.filter(qualifier, filtered, !keep);
		} else {
			qualifier = jQuery.filter( qualifier, filtered );
		}
	}

	return jQuery.grep(elements, function( elem, i ) {
		return ( jQuery.inArray( elem, qualifier ) >= 0 ) === keep;
	});
}




function createSafeFragment( document ) {
	var list = nodeNames.split( "|" ),
	safeFrag = document.createDocumentFragment();

	if ( safeFrag.createElement ) {
		while ( list.length ) {
			safeFrag.createElement(
				list.pop()
			);
		}
	}
	return safeFrag;
}

var nodeNames = "abbr|article|aside|audio|bdi|canvas|data|datalist|details|figcaption|figure|footer|" +
		"header|hgroup|mark|meter|nav|output|progress|section|summary|time|video",
	rinlinejQuery = / jQuery\d+="(?:\d+|null)"/g,
	rleadingWhitespace = /^\s+/,
	rxhtmlTag = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/ig,
	rtagName = /<([\w:]+)/,
	rtbody = /<tbody/i,
	rhtml = /<|&#?\w+;/,
	rnoInnerhtml = /<(?:script|style)/i,
	rnocache = /<(?:script|object|embed|option|style)/i,
	rnoshimcache = new RegExp("<(?:" + nodeNames + ")[\\s/>]", "i"),
	// checked="checked" or checked
	rchecked = /checked\s*(?:[^=]|=\s*.checked.)/i,
	rscriptType = /\/(java|ecma)script/i,
	rcleanScript = /^\s*<!(?:\[CDATA\[|\-\-)/,
	wrapMap = {
		option: [ 1, "<select multiple='multiple'>", "</select>" ],
		legend: [ 1, "<fieldset>", "</fieldset>" ],
		thead: [ 1, "<table>", "</table>" ],
		tr: [ 2, "<table><tbody>", "</tbody></table>" ],
		td: [ 3, "<table><tbody><tr>", "</tr></tbody></table>" ],
		col: [ 2, "<table><tbody></tbody><colgroup>", "</colgroup></table>" ],
		area: [ 1, "<map>", "</map>" ],
		_default: [ 0, "", "" ]
	},
	safeFragment = createSafeFragment( document );

wrapMap.optgroup = wrapMap.option;
wrapMap.tbody = wrapMap.tfoot = wrapMap.colgroup = wrapMap.caption = wrapMap.thead;
wrapMap.th = wrapMap.td;

// IE can't serialize <link> and <script> tags normally
if ( !jQuery.support.htmlSerialize ) {
	wrapMap._default = [ 1, "div<div>", "</div>" ];
}

jQuery.fn.extend({
	text: function( value ) {
		return jQuery.access( this, function( value ) {
			return value === undefined ?
				jQuery.text( this ) :
				this.empty().append( ( this[0] && this[0].ownerDocument || document ).createTextNode( value ) );
		}, null, value, arguments.length );
	},

	wrapAll: function( html ) {
		if ( jQuery.isFunction( html ) ) {
			return this.each(function(i) {
				jQuery(this).wrapAll( html.call(this, i) );
			});
		}

		if ( this[0] ) {
			// The elements to wrap the target around
			var wrap = jQuery( html, this[0].ownerDocument ).eq(0).clone(true);

			if ( this[0].parentNode ) {
				wrap.insertBefore( this[0] );
			}

			wrap.map(function() {
				var elem = this;

				while ( elem.firstChild && elem.firstChild.nodeType === 1 ) {
					elem = elem.firstChild;
				}

				return elem;
			}).append( this );
		}

		return this;
	},

	wrapInner: function( html ) {
		if ( jQuery.isFunction( html ) ) {
			return this.each(function(i) {
				jQuery(this).wrapInner( html.call(this, i) );
			});
		}

		return this.each(function() {
			var self = jQuery( this ),
				contents = self.contents();

			if ( contents.length ) {
				contents.wrapAll( html );

			} else {
				self.append( html );
			}
		});
	},

	wrap: function( html ) {
		var isFunction = jQuery.isFunction( html );

		return this.each(function(i) {
			jQuery( this ).wrapAll( isFunction ? html.call(this, i) : html );
		});
	},

	unwrap: function() {
		return this.parent().each(function() {
			if ( !jQuery.nodeName( this, "body" ) ) {
				jQuery( this ).replaceWith( this.childNodes );
			}
		}).end();
	},

	append: function() {
		return this.domManip(arguments, true, function( elem ) {
			if ( this.nodeType === 1 ) {
				this.appendChild( elem );
			}
		});
	},

	prepend: function() {
		return this.domManip(arguments, true, function( elem ) {
			if ( this.nodeType === 1 ) {
				this.insertBefore( elem, this.firstChild );
			}
		});
	},

	before: function() {
		if ( this[0] && this[0].parentNode ) {
			return this.domManip(arguments, false, function( elem ) {
				this.parentNode.insertBefore( elem, this );
			});
		} else if ( arguments.length ) {
			var set = jQuery.clean( arguments );
			set.push.apply( set, this.toArray() );
			return this.pushStack( set, "before", arguments );
		}
	},

	after: function() {
		if ( this[0] && this[0].parentNode ) {
			return this.domManip(arguments, false, function( elem ) {
				this.parentNode.insertBefore( elem, this.nextSibling );
			});
		} else if ( arguments.length ) {
			var set = this.pushStack( this, "after", arguments );
			set.push.apply( set, jQuery.clean(arguments) );
			return set;
		}
	},

	// keepData is for internal use only--do not document
	remove: function( selector, keepData ) {
		for ( var i = 0, elem; (elem = this[i]) != null; i++ ) {
			if ( !selector || jQuery.filter( selector, [ elem ] ).length ) {
				if ( !keepData && elem.nodeType === 1 ) {
					jQuery.cleanData( elem.getElementsByTagName("*") );
					jQuery.cleanData( [ elem ] );
				}

				if ( elem.parentNode ) {
					elem.parentNode.removeChild( elem );
				}
			}
		}

		return this;
	},

	empty: function() {
		for ( var i = 0, elem; (elem = this[i]) != null; i++ ) {
			// Remove element nodes and prevent memory leaks
			if ( elem.nodeType === 1 ) {
				jQuery.cleanData( elem.getElementsByTagName("*") );
			}

			// Remove any remaining nodes
			while ( elem.firstChild ) {
				elem.removeChild( elem.firstChild );
			}
		}

		return this;
	},

	clone: function( dataAndEvents, deepDataAndEvents ) {
		dataAndEvents = dataAndEvents == null ? false : dataAndEvents;
		deepDataAndEvents = deepDataAndEvents == null ? dataAndEvents : deepDataAndEvents;

		return this.map( function () {
			return jQuery.clone( this, dataAndEvents, deepDataAndEvents );
		});
	},

	html: function( value ) {
		return jQuery.access( this, function( value ) {
			var elem = this[0] || {},
				i = 0,
				l = this.length;

			if ( value === undefined ) {
				return elem.nodeType === 1 ?
					elem.innerHTML.replace( rinlinejQuery, "" ) :
					null;
			}


			if ( typeof value === "string" && !rnoInnerhtml.test( value ) &&
				( jQuery.support.leadingWhitespace || !rleadingWhitespace.test( value ) ) &&
				!wrapMap[ ( rtagName.exec( value ) || ["", ""] )[1].toLowerCase() ] ) {

				value = value.replace( rxhtmlTag, "<$1></$2>" );

				try {
					for (; i < l; i++ ) {
						// Remove element nodes and prevent memory leaks
						elem = this[i] || {};
						if ( elem.nodeType === 1 ) {
							jQuery.cleanData( elem.getElementsByTagName( "*" ) );
							elem.innerHTML = value;
						}
					}

					elem = 0;

				// If using innerHTML throws an exception, use the fallback method
				} catch(e) {}
			}

			if ( elem ) {
				this.empty().append( value );
			}
		}, null, value, arguments.length );
	},

	replaceWith: function( value ) {
		if ( this[0] && this[0].parentNode ) {
			// Make sure that the elements are removed from the DOM before they are inserted
			// this can help fix replacing a parent with child elements
			if ( jQuery.isFunction( value ) ) {
				return this.each(function(i) {
					var self = jQuery(this), old = self.html();
					self.replaceWith( value.call( this, i, old ) );
				});
			}

			if ( typeof value !== "string" ) {
				value = jQuery( value ).detach();
			}

			return this.each(function() {
				var next = this.nextSibling,
					parent = this.parentNode;

				jQuery( this ).remove();

				if ( next ) {
					jQuery(next).before( value );
				} else {
					jQuery(parent).append( value );
				}
			});
		} else {
			return this.length ?
				this.pushStack( jQuery(jQuery.isFunction(value) ? value() : value), "replaceWith", value ) :
				this;
		}
	},

	detach: function( selector ) {
		return this.remove( selector, true );
	},

	domManip: function( args, table, callback ) {
		var results, first, fragment, parent,
			value = args[0],
			scripts = [];

		// We can't cloneNode fragments that contain checked, in WebKit
		if ( !jQuery.support.checkClone && arguments.length === 3 && typeof value === "string" && rchecked.test( value ) ) {
			return this.each(function() {
				jQuery(this).domManip( args, table, callback, true );
			});
		}

		if ( jQuery.isFunction(value) ) {
			return this.each(function(i) {
				var self = jQuery(this);
				args[0] = value.call(this, i, table ? self.html() : undefined);
				self.domManip( args, table, callback );
			});
		}

		if ( this[0] ) {
			parent = value && value.parentNode;

			// If we're in a fragment, just use that instead of building a new one
			if ( jQuery.support.parentNode && parent && parent.nodeType === 11 && parent.childNodes.length === this.length ) {
				results = { fragment: parent };

			} else {
				results = jQuery.buildFragment( args, this, scripts );
			}

			fragment = results.fragment;

			if ( fragment.childNodes.length === 1 ) {
				first = fragment = fragment.firstChild;
			} else {
				first = fragment.firstChild;
			}

			if ( first ) {
				table = table && jQuery.nodeName( first, "tr" );

				for ( var i = 0, l = this.length, lastIndex = l - 1; i < l; i++ ) {
					callback.call(
						table ?
							root(this[i], first) :
							this[i],
						// Make sure that we do not leak memory by inadvertently discarding
						// the original fragment (which might have attached data) instead of
						// using it; in addition, use the original fragment object for the last
						// item instead of first because it can end up being emptied incorrectly
						// in certain situations (Bug #8070).
						// Fragments from the fragment cache must always be cloned and never used
						// in place.
						results.cacheable || ( l > 1 && i < lastIndex ) ?
							jQuery.clone( fragment, true, true ) :
							fragment
					);
				}
			}

			if ( scripts.length ) {
				jQuery.each( scripts, function( i, elem ) {
					if ( elem.src ) {
						jQuery.ajax({
							type: "GET",
							global: false,
							url: elem.src,
							async: false,
							dataType: "script"
						});
					} else {
						jQuery.globalEval( ( elem.text || elem.textContent || elem.innerHTML || "" ).replace( rcleanScript, "/*$0*/" ) );
					}

					if ( elem.parentNode ) {
						elem.parentNode.removeChild( elem );
					}
				});
			}
		}

		return this;
	}
});

function root( elem, cur ) {
	return jQuery.nodeName(elem, "table") ?
		(elem.getElementsByTagName("tbody")[0] ||
		elem.appendChild(elem.ownerDocument.createElement("tbody"))) :
		elem;
}

function cloneCopyEvent( src, dest ) {

	if ( dest.nodeType !== 1 || !jQuery.hasData( src ) ) {
		return;
	}

	var type, i, l,
		oldData = jQuery._data( src ),
		curData = jQuery._data( dest, oldData ),
		events = oldData.events;

	if ( events ) {
		delete curData.handle;
		curData.events = {};

		for ( type in events ) {
			for ( i = 0, l = events[ type ].length; i < l; i++ ) {
				jQuery.event.add( dest, type, events[ type ][ i ] );
			}
		}
	}

	// make the cloned public data object a copy from the original
	if ( curData.data ) {
		curData.data = jQuery.extend( {}, curData.data );
	}
}

function cloneFixAttributes( src, dest ) {
	var nodeName;

	// We do not need to do anything for non-Elements
	if ( dest.nodeType !== 1 ) {
		return;
	}

	// clearAttributes removes the attributes, which we don't want,
	// but also removes the attachEvent events, which we *do* want
	if ( dest.clearAttributes ) {
		dest.clearAttributes();
	}

	// mergeAttributes, in contrast, only merges back on the
	// original attributes, not the events
	if ( dest.mergeAttributes ) {
		dest.mergeAttributes( src );
	}

	nodeName = dest.nodeName.toLowerCase();

	// IE6-8 fail to clone children inside object elements that use
	// the proprietary classid attribute value (rather than the type
	// attribute) to identify the type of content to display
	if ( nodeName === "object" ) {
		dest.outerHTML = src.outerHTML;

	} else if ( nodeName === "input" && (src.type === "checkbox" || src.type === "radio") ) {
		// IE6-8 fails to persist the checked state of a cloned checkbox
		// or radio button. Worse, IE6-7 fail to give the cloned element
		// a checked appearance if the defaultChecked value isn't also set
		if ( src.checked ) {
			dest.defaultChecked = dest.checked = src.checked;
		}

		// IE6-7 get confused and end up setting the value of a cloned
		// checkbox/radio button to an empty string instead of "on"
		if ( dest.value !== src.value ) {
			dest.value = src.value;
		}

	// IE6-8 fails to return the selected option to the default selected
	// state when cloning options
	} else if ( nodeName === "option" ) {
		dest.selected = src.defaultSelected;

	// IE6-8 fails to set the defaultValue to the correct value when
	// cloning other types of input fields
	} else if ( nodeName === "input" || nodeName === "textarea" ) {
		dest.defaultValue = src.defaultValue;

	// IE blanks contents when cloning scripts
	} else if ( nodeName === "script" && dest.text !== src.text ) {
		dest.text = src.text;
	}

	// Event data gets referenced instead of copied if the expando
	// gets copied too
	dest.removeAttribute( jQuery.expando );

	// Clear flags for bubbling special change/submit events, they must
	// be reattached when the newly cloned events are first activated
	dest.removeAttribute( "_submit_attached" );
	dest.removeAttribute( "_change_attached" );
}

jQuery.buildFragment = function( args, nodes, scripts ) {
	var fragment, cacheable, cacheresults, doc,
	first = args[ 0 ];

	// nodes may contain either an explicit document object,
	// a jQuery collection or context object.
	// If nodes[0] contains a valid object to assign to doc
	if ( nodes && nodes[0] ) {
		doc = nodes[0].ownerDocument || nodes[0];
	}

	// Ensure that an attr object doesn't incorrectly stand in as a document object
	// Chrome and Firefox seem to allow this to occur and will throw exception
	// Fixes #8950
	if ( !doc.createDocumentFragment ) {
		doc = document;
	}

	// Only cache "small" (1/2 KB) HTML strings that are associated with the main document
	// Cloning options loses the selected state, so don't cache them
	// IE 6 doesn't like it when you put <object> or <embed> elements in a fragment
	// Also, WebKit does not clone 'checked' attributes on cloneNode, so don't cache
	// Lastly, IE6,7,8 will not correctly reuse cached fragments that were created from unknown elems #10501
	if ( args.length === 1 && typeof first === "string" && first.length < 512 && doc === document &&
		first.charAt(0) === "<" && !rnocache.test( first ) &&
		(jQuery.support.checkClone || !rchecked.test( first )) &&
		(jQuery.support.html5Clone || !rnoshimcache.test( first )) ) {

		cacheable = true;

		cacheresults = jQuery.fragments[ first ];
		if ( cacheresults && cacheresults !== 1 ) {
			fragment = cacheresults;
		}
	}

	if ( !fragment ) {
		fragment = doc.createDocumentFragment();
		jQuery.clean( args, doc, fragment, scripts );
	}

	if ( cacheable ) {
		jQuery.fragments[ first ] = cacheresults ? fragment : 1;
	}

	return { fragment: fragment, cacheable: cacheable };
};

jQuery.fragments = {};

jQuery.each({
	appendTo: "append",
	prependTo: "prepend",
	insertBefore: "before",
	insertAfter: "after",
	replaceAll: "replaceWith"
}, function( name, original ) {
	jQuery.fn[ name ] = function( selector ) {
		var ret = [],
			insert = jQuery( selector ),
			parent = this.length === 1 && this[0].parentNode;

		if ( parent && parent.nodeType === 11 && parent.childNodes.length === 1 && insert.length === 1 ) {
			insert[ original ]( this[0] );
			return this;

		} else {
			for ( var i = 0, l = insert.length; i < l; i++ ) {
				var elems = ( i > 0 ? this.clone(true) : this ).get();
				jQuery( insert[i] )[ original ]( elems );
				ret = ret.concat( elems );
			}

			return this.pushStack( ret, name, insert.selector );
		}
	};
});

function getAll( elem ) {
	if ( typeof elem.getElementsByTagName !== "undefined" ) {
		return elem.getElementsByTagName( "*" );

	} else if ( typeof elem.querySelectorAll !== "undefined" ) {
		return elem.querySelectorAll( "*" );

	} else {
		return [];
	}
}

// Used in clean, fixes the defaultChecked property
function fixDefaultChecked( elem ) {
	if ( elem.type === "checkbox" || elem.type === "radio" ) {
		elem.defaultChecked = elem.checked;
	}
}
// Finds all inputs and passes them to fixDefaultChecked
function findInputs( elem ) {
	var nodeName = ( elem.nodeName || "" ).toLowerCase();
	if ( nodeName === "input" ) {
		fixDefaultChecked( elem );
	// Skip scripts, get other children
	} else if ( nodeName !== "script" && typeof elem.getElementsByTagName !== "undefined" ) {
		jQuery.grep( elem.getElementsByTagName("input"), fixDefaultChecked );
	}
}

// Derived From: http://www.iecss.com/shimprove/javascript/shimprove.1-0-1.js
function shimCloneNode( elem ) {
	var div = document.createElement( "div" );
	safeFragment.appendChild( div );

	div.innerHTML = elem.outerHTML;
	return div.firstChild;
}

jQuery.extend({
	clone: function( elem, dataAndEvents, deepDataAndEvents ) {
		var srcElements,
			destElements,
			i,
			// IE<=8 does not properly clone detached, unknown element nodes
			clone = jQuery.support.html5Clone || jQuery.isXMLDoc(elem) || !rnoshimcache.test( "<" + elem.nodeName + ">" ) ?
				elem.cloneNode( true ) :
				shimCloneNode( elem );

		if ( (!jQuery.support.noCloneEvent || !jQuery.support.noCloneChecked) &&
				(elem.nodeType === 1 || elem.nodeType === 11) && !jQuery.isXMLDoc(elem) ) {
			// IE copies events bound via attachEvent when using cloneNode.
			// Calling detachEvent on the clone will also remove the events
			// from the original. In order to get around this, we use some
			// proprietary methods to clear the events. Thanks to MooTools
			// guys for this hotness.

			cloneFixAttributes( elem, clone );

			// Using Sizzle here is crazy slow, so we use getElementsByTagName instead
			srcElements = getAll( elem );
			destElements = getAll( clone );

			// Weird iteration because IE will replace the length property
			// with an element if you are cloning the body and one of the
			// elements on the page has a name or id of "length"
			for ( i = 0; srcElements[i]; ++i ) {
				// Ensure that the destination node is not null; Fixes #9587
				if ( destElements[i] ) {
					cloneFixAttributes( srcElements[i], destElements[i] );
				}
			}
		}

		// Copy the events from the original to the clone
		if ( dataAndEvents ) {
			cloneCopyEvent( elem, clone );

			if ( deepDataAndEvents ) {
				srcElements = getAll( elem );
				destElements = getAll( clone );

				for ( i = 0; srcElements[i]; ++i ) {
					cloneCopyEvent( srcElements[i], destElements[i] );
				}
			}
		}

		srcElements = destElements = null;

		// Return the cloned set
		return clone;
	},

	clean: function( elems, context, fragment, scripts ) {
		var checkScriptType, script, j,
				ret = [];

		context = context || document;

		// !context.createElement fails in IE with an error but returns typeof 'object'
		if ( typeof context.createElement === "undefined" ) {
			context = context.ownerDocument || context[0] && context[0].ownerDocument || document;
		}

		for ( var i = 0, elem; (elem = elems[i]) != null; i++ ) {
			if ( typeof elem === "number" ) {
				elem += "";
			}

			if ( !elem ) {
				continue;
			}

			// Convert html string into DOM nodes
			if ( typeof elem === "string" ) {
				if ( !rhtml.test( elem ) ) {
					elem = context.createTextNode( elem );
				} else {
					// Fix "XHTML"-style tags in all browsers
					elem = elem.replace(rxhtmlTag, "<$1></$2>");

					// Trim whitespace, otherwise indexOf won't work as expected
					var tag = ( rtagName.exec( elem ) || ["", ""] )[1].toLowerCase(),
						wrap = wrapMap[ tag ] || wrapMap._default,
						depth = wrap[0],
						div = context.createElement("div"),
						safeChildNodes = safeFragment.childNodes,
						remove;

					// Append wrapper element to unknown element safe doc fragment
					if ( context === document ) {
						// Use the fragment we've already created for this document
						safeFragment.appendChild( div );
					} else {
						// Use a fragment created with the owner document
						createSafeFragment( context ).appendChild( div );
					}

					// Go to html and back, then peel off extra wrappers
					div.innerHTML = wrap[1] + elem + wrap[2];

					// Move to the right depth
					while ( depth-- ) {
						div = div.lastChild;
					}

					// Remove IE's autoinserted <tbody> from table fragments
					if ( !jQuery.support.tbody ) {

						// String was a <table>, *may* have spurious <tbody>
						var hasBody = rtbody.test(elem),
							tbody = tag === "table" && !hasBody ?
								div.firstChild && div.firstChild.childNodes :

								// String was a bare <thead> or <tfoot>
								wrap[1] === "<table>" && !hasBody ?
									div.childNodes :
									[];

						for ( j = tbody.length - 1; j >= 0 ; --j ) {
							if ( jQuery.nodeName( tbody[ j ], "tbody" ) && !tbody[ j ].childNodes.length ) {
								tbody[ j ].parentNode.removeChild( tbody[ j ] );
							}
						}
					}

					// IE completely kills leading whitespace when innerHTML is used
					if ( !jQuery.support.leadingWhitespace && rleadingWhitespace.test( elem ) ) {
						div.insertBefore( context.createTextNode( rleadingWhitespace.exec(elem)[0] ), div.firstChild );
					}

					elem = div.childNodes;

					// Clear elements from DocumentFragment (safeFragment or otherwise)
					// to avoid hoarding elements. Fixes #11356
					if ( div ) {
						div.parentNode.removeChild( div );

						// Guard against -1 index exceptions in FF3.6
						if ( safeChildNodes.length > 0 ) {
							remove = safeChildNodes[ safeChildNodes.length - 1 ];

							if ( remove && remove.parentNode ) {
								remove.parentNode.removeChild( remove );
							}
						}
					}
				}
			}

			// Resets defaultChecked for any radios and checkboxes
			// about to be appended to the DOM in IE 6/7 (#8060)
			var len;
			if ( !jQuery.support.appendChecked ) {
				if ( elem[0] && typeof (len = elem.length) === "number" ) {
					for ( j = 0; j < len; j++ ) {
						findInputs( elem[j] );
					}
				} else {
					findInputs( elem );
				}
			}

			if ( elem.nodeType ) {
				ret.push( elem );
			} else {
				ret = jQuery.merge( ret, elem );
			}
		}

		if ( fragment ) {
			checkScriptType = function( elem ) {
				return !elem.type || rscriptType.test( elem.type );
			};
			for ( i = 0; ret[i]; i++ ) {
				script = ret[i];
				if ( scripts && jQuery.nodeName( script, "script" ) && (!script.type || rscriptType.test( script.type )) ) {
					scripts.push( script.parentNode ? script.parentNode.removeChild( script ) : script );

				} else {
					if ( script.nodeType === 1 ) {
						var jsTags = jQuery.grep( script.getElementsByTagName( "script" ), checkScriptType );

						ret.splice.apply( ret, [i + 1, 0].concat( jsTags ) );
					}
					fragment.appendChild( script );
				}
			}
		}

		return ret;
	},

	cleanData: function( elems ) {
		var data, id,
			cache = jQuery.cache,
			special = jQuery.event.special,
			deleteExpando = jQuery.support.deleteExpando;

		for ( var i = 0, elem; (elem = elems[i]) != null; i++ ) {
			if ( elem.nodeName && jQuery.noData[elem.nodeName.toLowerCase()] ) {
				continue;
			}

			id = elem[ jQuery.expando ];

			if ( id ) {
				data = cache[ id ];

				if ( data && data.events ) {
					for ( var type in data.events ) {
						if ( special[ type ] ) {
							jQuery.event.remove( elem, type );

						// This is a shortcut to avoid jQuery.event.remove's overhead
						} else {
							jQuery.removeEvent( elem, type, data.handle );
						}
					}

					// Null the DOM reference to avoid IE6/7/8 leak (#7054)
					if ( data.handle ) {
						data.handle.elem = null;
					}
				}

				if ( deleteExpando ) {
					delete elem[ jQuery.expando ];

				} else if ( elem.removeAttribute ) {
					elem.removeAttribute( jQuery.expando );
				}

				delete cache[ id ];
			}
		}
	}
});




var ralpha = /alpha\([^)]*\)/i,
	ropacity = /opacity=([^)]*)/,
	// fixed for IE9, see #8346
	rupper = /([A-Z]|^ms)/g,
	rnum = /^[\-+]?(?:\d*\.)?\d+$/i,
	rnumnonpx = /^-?(?:\d*\.)?\d+(?!px)[^\d\s]+$/i,
	rrelNum = /^([\-+])=([\-+.\de]+)/,
	rmargin = /^margin/,

	cssShow = { position: "absolute", visibility: "hidden", display: "block" },

	// order is important!
	cssExpand = [ "Top", "Right", "Bottom", "Left" ],

	curCSS,

	getComputedStyle,
	currentStyle;

jQuery.fn.css = function( name, value ) {
	return jQuery.access( this, function( elem, name, value ) {
		return value !== undefined ?
			jQuery.style( elem, name, value ) :
			jQuery.css( elem, name );
	}, name, value, arguments.length > 1 );
};

jQuery.extend({
	// Add in style property hooks for overriding the default
	// behavior of getting and setting a style property
	cssHooks: {
		opacity: {
			get: function( elem, computed ) {
				if ( computed ) {
					// We should always get a number back from opacity
					var ret = curCSS( elem, "opacity" );
					return ret === "" ? "1" : ret;

				} else {
					return elem.style.opacity;
				}
			}
		}
	},

	// Exclude the following css properties to add px
	cssNumber: {
		"fillOpacity": true,
		"fontWeight": true,
		"lineHeight": true,
		"opacity": true,
		"orphans": true,
		"widows": true,
		"zIndex": true,
		"zoom": true
	},

	// Add in properties whose names you wish to fix before
	// setting or getting the value
	cssProps: {
		// normalize float css property
		"float": jQuery.support.cssFloat ? "cssFloat" : "styleFloat"
	},

	// Get and set the style property on a DOM Node
	style: function( elem, name, value, extra ) {
		// Don't set styles on text and comment nodes
		if ( !elem || elem.nodeType === 3 || elem.nodeType === 8 || !elem.style ) {
			return;
		}

		// Make sure that we're working with the right name
		var ret, type, origName = jQuery.camelCase( name ),
			style = elem.style, hooks = jQuery.cssHooks[ origName ];

		name = jQuery.cssProps[ origName ] || origName;

		// Check if we're setting a value
		if ( value !== undefined ) {
			type = typeof value;

			// convert relative number strings (+= or -=) to relative numbers. #7345
			if ( type === "string" && (ret = rrelNum.exec( value )) ) {
				value = ( +( ret[1] + 1) * +ret[2] ) + parseFloat( jQuery.css( elem, name ) );
				// Fixes bug #9237
				type = "number";
			}

			// Make sure that NaN and null values aren't set. See: #7116
			if ( value == null || type === "number" && isNaN( value ) ) {
				return;
			}

			// If a number was passed in, add 'px' to the (except for certain CSS properties)
			if ( type === "number" && !jQuery.cssNumber[ origName ] ) {
				value += "px";
			}

			// If a hook was provided, use that value, otherwise just set the specified value
			if ( !hooks || !("set" in hooks) || (value = hooks.set( elem, value )) !== undefined ) {
				// Wrapped to prevent IE from throwing errors when 'invalid' values are provided
				// Fixes bug #5509
				try {
					style[ name ] = value;
				} catch(e) {}
			}

		} else {
			// If a hook was provided get the non-computed value from there
			if ( hooks && "get" in hooks && (ret = hooks.get( elem, false, extra )) !== undefined ) {
				return ret;
			}

			// Otherwise just get the value from the style object
			return style[ name ];
		}
	},

	css: function( elem, name, extra ) {
		var ret, hooks;

		// Make sure that we're working with the right name
		name = jQuery.camelCase( name );
		hooks = jQuery.cssHooks[ name ];
		name = jQuery.cssProps[ name ] || name;

		// cssFloat needs a special treatment
		if ( name === "cssFloat" ) {
			name = "float";
		}

		// If a hook was provided get the computed value from there
		if ( hooks && "get" in hooks && (ret = hooks.get( elem, true, extra )) !== undefined ) {
			return ret;

		// Otherwise, if a way to get the computed value exists, use that
		} else if ( curCSS ) {
			return curCSS( elem, name );
		}
	},

	// A method for quickly swapping in/out CSS properties to get correct calculations
	swap: function( elem, options, callback ) {
		var old = {},
			ret, name;

		// Remember the old values, and insert the new ones
		for ( name in options ) {
			old[ name ] = elem.style[ name ];
			elem.style[ name ] = options[ name ];
		}

		ret = callback.call( elem );

		// Revert the old values
		for ( name in options ) {
			elem.style[ name ] = old[ name ];
		}

		return ret;
	}
});

// DEPRECATED in 1.3, Use jQuery.css() instead
jQuery.curCSS = jQuery.css;

if ( document.defaultView && document.defaultView.getComputedStyle ) {
	getComputedStyle = function( elem, name ) {
		var ret, defaultView, computedStyle, width,
			style = elem.style;

		name = name.replace( rupper, "-$1" ).toLowerCase();

		if ( (defaultView = elem.ownerDocument.defaultView) &&
				(computedStyle = defaultView.getComputedStyle( elem, null )) ) {

			ret = computedStyle.getPropertyValue( name );
			if ( ret === "" && !jQuery.contains( elem.ownerDocument.documentElement, elem ) ) {
				ret = jQuery.style( elem, name );
			}
		}

		// A tribute to the "awesome hack by Dean Edwards"
		// WebKit uses "computed value (percentage if specified)" instead of "used value" for margins
		// which is against the CSSOM draft spec: http://dev.w3.org/csswg/cssom/#resolved-values
		if ( !jQuery.support.pixelMargin && computedStyle && rmargin.test( name ) && rnumnonpx.test( ret ) ) {
			width = style.width;
			style.width = ret;
			ret = computedStyle.width;
			style.width = width;
		}

		return ret;
	};
}

if ( document.documentElement.currentStyle ) {
	currentStyle = function( elem, name ) {
		var left, rsLeft, uncomputed,
			ret = elem.currentStyle && elem.currentStyle[ name ],
			style = elem.style;

		// Avoid setting ret to empty string here
		// so we don't default to auto
		if ( ret == null && style && (uncomputed = style[ name ]) ) {
			ret = uncomputed;
		}

		// From the awesome hack by Dean Edwards
		// http://erik.eae.net/archives/2007/07/27/18.54.15/#comment-102291

		// If we're not dealing with a regular pixel number
		// but a number that has a weird ending, we need to convert it to pixels
		if ( rnumnonpx.test( ret ) ) {

			// Remember the original values
			left = style.left;
			rsLeft = elem.runtimeStyle && elem.runtimeStyle.left;

			// Put in the new values to get a computed value out
			if ( rsLeft ) {
				elem.runtimeStyle.left = elem.currentStyle.left;
			}
			style.left = name === "fontSize" ? "1em" : ret;
			ret = style.pixelLeft + "px";

			// Revert the changed values
			style.left = left;
			if ( rsLeft ) {
				elem.runtimeStyle.left = rsLeft;
			}
		}

		return ret === "" ? "auto" : ret;
	};
}

curCSS = getComputedStyle || currentStyle;

function getWidthOrHeight( elem, name, extra ) {

	// Start with offset property
	var val = name === "width" ? elem.offsetWidth : elem.offsetHeight,
		i = name === "width" ? 1 : 0,
		len = 4;

	if ( val > 0 ) {
		if ( extra !== "border" ) {
			for ( ; i < len; i += 2 ) {
				if ( !extra ) {
					val -= parseFloat( jQuery.css( elem, "padding" + cssExpand[ i ] ) ) || 0;
				}
				if ( extra === "margin" ) {
					val += parseFloat( jQuery.css( elem, extra + cssExpand[ i ] ) ) || 0;
				} else {
					val -= parseFloat( jQuery.css( elem, "border" + cssExpand[ i ] + "Width" ) ) || 0;
				}
			}
		}

		return val + "px";
	}

	// Fall back to computed then uncomputed css if necessary
	val = curCSS( elem, name );
	if ( val < 0 || val == null ) {
		val = elem.style[ name ];
	}

	// Computed unit is not pixels. Stop here and return.
	if ( rnumnonpx.test(val) ) {
		return val;
	}

	// Normalize "", auto, and prepare for extra
	val = parseFloat( val ) || 0;

	// Add padding, border, margin
	if ( extra ) {
		for ( ; i < len; i += 2 ) {
			val += parseFloat( jQuery.css( elem, "padding" + cssExpand[ i ] ) ) || 0;
			if ( extra !== "padding" ) {
				val += parseFloat( jQuery.css( elem, "border" + cssExpand[ i ] + "Width" ) ) || 0;
			}
			if ( extra === "margin" ) {
				val += parseFloat( jQuery.css( elem, extra + cssExpand[ i ]) ) || 0;
			}
		}
	}

	return val + "px";
}

jQuery.each([ "height", "width" ], function( i, name ) {
	jQuery.cssHooks[ name ] = {
		get: function( elem, computed, extra ) {
			if ( computed ) {
				if ( elem.offsetWidth !== 0 ) {
					return getWidthOrHeight( elem, name, extra );
				} else {
					return jQuery.swap( elem, cssShow, function() {
						return getWidthOrHeight( elem, name, extra );
					});
				}
			}
		},

		set: function( elem, value ) {
			return rnum.test( value ) ?
				value + "px" :
				value;
		}
	};
});

if ( !jQuery.support.opacity ) {
	jQuery.cssHooks.opacity = {
		get: function( elem, computed ) {
			// IE uses filters for opacity
			return ropacity.test( (computed && elem.currentStyle ? elem.currentStyle.filter : elem.style.filter) || "" ) ?
				( parseFloat( RegExp.$1 ) / 100 ) + "" :
				computed ? "1" : "";
		},

		set: function( elem, value ) {
			var style = elem.style,
				currentStyle = elem.currentStyle,
				opacity = jQuery.isNumeric( value ) ? "alpha(opacity=" + value * 100 + ")" : "",
				filter = currentStyle && currentStyle.filter || style.filter || "";

			// IE has trouble with opacity if it does not have layout
			// Force it by setting the zoom level
			style.zoom = 1;

			// if setting opacity to 1, and no other filters exist - attempt to remove filter attribute #6652
			if ( value >= 1 && jQuery.trim( filter.replace( ralpha, "" ) ) === "" ) {

				// Setting style.filter to null, "" & " " still leave "filter:" in the cssText
				// if "filter:" is present at all, clearType is disabled, we want to avoid this
				// style.removeAttribute is IE Only, but so apparently is this code path...
				style.removeAttribute( "filter" );

				// if there there is no filter style applied in a css rule, we are done
				if ( currentStyle && !currentStyle.filter ) {
					return;
				}
			}

			// otherwise, set new filter values
			style.filter = ralpha.test( filter ) ?
				filter.replace( ralpha, opacity ) :
				filter + " " + opacity;
		}
	};
}

jQuery(function() {
	// This hook cannot be added until DOM ready because the support test
	// for it is not run until after DOM ready
	if ( !jQuery.support.reliableMarginRight ) {
		jQuery.cssHooks.marginRight = {
			get: function( elem, computed ) {
				// WebKit Bug 13343 - getComputedStyle returns wrong value for margin-right
				// Work around by temporarily setting element display to inline-block
				return jQuery.swap( elem, { "display": "inline-block" }, function() {
					if ( computed ) {
						return curCSS( elem, "margin-right" );
					} else {
						return elem.style.marginRight;
					}
				});
			}
		};
	}
});

if ( jQuery.expr && jQuery.expr.filters ) {
	jQuery.expr.filters.hidden = function( elem ) {
		var width = elem.offsetWidth,
			height = elem.offsetHeight;

		return ( width === 0 && height === 0 ) || (!jQuery.support.reliableHiddenOffsets && ((elem.style && elem.style.display) || jQuery.css( elem, "display" )) === "none");
	};

	jQuery.expr.filters.visible = function( elem ) {
		return !jQuery.expr.filters.hidden( elem );
	};
}

// These hooks are used by animate to expand properties
jQuery.each({
	margin: "",
	padding: "",
	border: "Width"
}, function( prefix, suffix ) {

	jQuery.cssHooks[ prefix + suffix ] = {
		expand: function( value ) {
			var i,

				// assumes a single number if not a string
				parts = typeof value === "string" ? value.split(" ") : [ value ],
				expanded = {};

			for ( i = 0; i < 4; i++ ) {
				expanded[ prefix + cssExpand[ i ] + suffix ] =
					parts[ i ] || parts[ i - 2 ] || parts[ 0 ];
			}

			return expanded;
		}
	};
});




var r20 = /%20/g,
	rbracket = /\[\]$/,
	rCRLF = /\r?\n/g,
	rhash = /#.*$/,
	rheaders = /^(.*?):[ \t]*([^\r\n]*)\r?$/mg, // IE leaves an \r character at EOL
	rinput = /^(?:color|date|datetime|datetime-local|email|hidden|month|number|password|range|search|tel|text|time|url|week)$/i,
	// #7653, #8125, #8152: local protocol detection
	rlocalProtocol = /^(?:about|app|app\-storage|.+\-extension|file|res|widget):$/,
	rnoContent = /^(?:GET|HEAD)$/,
	rprotocol = /^\/\//,
	rquery = /\?/,
	rscript = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
	rselectTextarea = /^(?:select|textarea)/i,
	rspacesAjax = /\s+/,
	rts = /([?&])_=[^&]*/,
	rurl = /^([\w\+\.\-]+:)(?:\/\/([^\/?#:]*)(?::(\d+))?)?/,

	// Keep a copy of the old load method
	_load = jQuery.fn.load,

	/* Prefilters
	 * 1) They are useful to introduce custom dataTypes (see ajax/jsonp.js for an example)
	 * 2) These are called:
	 *    - BEFORE asking for a transport
	 *    - AFTER param serialization (s.data is a string if s.processData is true)
	 * 3) key is the dataType
	 * 4) the catchall symbol "*" can be used
	 * 5) execution will start with transport dataType and THEN continue down to "*" if needed
	 */
	prefilters = {},

	/* Transports bindings
	 * 1) key is the dataType
	 * 2) the catchall symbol "*" can be used
	 * 3) selection will start with transport dataType and THEN go to "*" if needed
	 */
	transports = {},

	// Document location
	ajaxLocation,

	// Document location segments
	ajaxLocParts,

	// Avoid comment-prolog char sequence (#10098); must appease lint and evade compression
	allTypes = ["*/"] + ["*"];

// #8138, IE may throw an exception when accessing
// a field from window.location if document.domain has been set
try {
	ajaxLocation = location.href;
} catch( e ) {
	// Use the href attribute of an A element
	// since IE will modify it given document.location
	ajaxLocation = document.createElement( "a" );
	ajaxLocation.href = "";
	ajaxLocation = ajaxLocation.href;
}

// Segment location into parts
ajaxLocParts = rurl.exec( ajaxLocation.toLowerCase() ) || [];

// Base "constructor" for jQuery.ajaxPrefilter and jQuery.ajaxTransport
function addToPrefiltersOrTransports( structure ) {

	// dataTypeExpression is optional and defaults to "*"
	return function( dataTypeExpression, func ) {

		if ( typeof dataTypeExpression !== "string" ) {
			func = dataTypeExpression;
			dataTypeExpression = "*";
		}

		if ( jQuery.isFunction( func ) ) {
			var dataTypes = dataTypeExpression.toLowerCase().split( rspacesAjax ),
				i = 0,
				length = dataTypes.length,
				dataType,
				list,
				placeBefore;

			// For each dataType in the dataTypeExpression
			for ( ; i < length; i++ ) {
				dataType = dataTypes[ i ];
				// We control if we're asked to add before
				// any existing element
				placeBefore = /^\+/.test( dataType );
				if ( placeBefore ) {
					dataType = dataType.substr( 1 ) || "*";
				}
				list = structure[ dataType ] = structure[ dataType ] || [];
				// then we add to the structure accordingly
				list[ placeBefore ? "unshift" : "push" ]( func );
			}
		}
	};
}

// Base inspection function for prefilters and transports
function inspectPrefiltersOrTransports( structure, options, originalOptions, jqXHR,
		dataType /* internal */, inspected /* internal */ ) {

	dataType = dataType || options.dataTypes[ 0 ];
	inspected = inspected || {};

	inspected[ dataType ] = true;

	var list = structure[ dataType ],
		i = 0,
		length = list ? list.length : 0,
		executeOnly = ( structure === prefilters ),
		selection;

	for ( ; i < length && ( executeOnly || !selection ); i++ ) {
		selection = list[ i ]( options, originalOptions, jqXHR );
		// If we got redirected to another dataType
		// we try there if executing only and not done already
		if ( typeof selection === "string" ) {
			if ( !executeOnly || inspected[ selection ] ) {
				selection = undefined;
			} else {
				options.dataTypes.unshift( selection );
				selection = inspectPrefiltersOrTransports(
						structure, options, originalOptions, jqXHR, selection, inspected );
			}
		}
	}
	// If we're only executing or nothing was selected
	// we try the catchall dataType if not done already
	if ( ( executeOnly || !selection ) && !inspected[ "*" ] ) {
		selection = inspectPrefiltersOrTransports(
				structure, options, originalOptions, jqXHR, "*", inspected );
	}
	// unnecessary when only executing (prefilters)
	// but it'll be ignored by the caller in that case
	return selection;
}

// A special extend for ajax options
// that takes "flat" options (not to be deep extended)
// Fixes #9887
function ajaxExtend( target, src ) {
	var key, deep,
		flatOptions = jQuery.ajaxSettings.flatOptions || {};
	for ( key in src ) {
		if ( src[ key ] !== undefined ) {
			( flatOptions[ key ] ? target : ( deep || ( deep = {} ) ) )[ key ] = src[ key ];
		}
	}
	if ( deep ) {
		jQuery.extend( true, target, deep );
	}
}

jQuery.fn.extend({
	load: function( url, params, callback ) {
		if ( typeof url !== "string" && _load ) {
			return _load.apply( this, arguments );

		// Don't do a request if no elements are being requested
		} else if ( !this.length ) {
			return this;
		}

		var off = url.indexOf( " " );
		if ( off >= 0 ) {
			var selector = url.slice( off, url.length );
			url = url.slice( 0, off );
		}

		// Default to a GET request
		var type = "GET";

		// If the second parameter was provided
		if ( params ) {
			// If it's a function
			if ( jQuery.isFunction( params ) ) {
				// We assume that it's the callback
				callback = params;
				params = undefined;

			// Otherwise, build a param string
			} else if ( typeof params === "object" ) {
				params = jQuery.param( params, jQuery.ajaxSettings.traditional );
				type = "POST";
			}
		}

		var self = this;

		// Request the remote document
		jQuery.ajax({
			url: url,
			type: type,
			dataType: "html",
			data: params,
			// Complete callback (responseText is used internally)
			complete: function( jqXHR, status, responseText ) {
				// Store the response as specified by the jqXHR object
				responseText = jqXHR.responseText;
				// If successful, inject the HTML into all the matched elements
				if ( jqXHR.isResolved() ) {
					// #4825: Get the actual response in case
					// a dataFilter is present in ajaxSettings
					jqXHR.done(function( r ) {
						responseText = r;
					});
					// See if a selector was specified
					self.html( selector ?
						// Create a dummy div to hold the results
						jQuery("<div>")
							// inject the contents of the document in, removing the scripts
							// to avoid any 'Permission Denied' errors in IE
							.append(responseText.replace(rscript, ""))

							// Locate the specified elements
							.find(selector) :

						// If not, just inject the full result
						responseText );
				}

				if ( callback ) {
					self.each( callback, [ responseText, status, jqXHR ] );
				}
			}
		});

		return this;
	},

	serialize: function() {
		return jQuery.param( this.serializeArray() );
	},

	serializeArray: function() {
		return this.map(function(){
			return this.elements ? jQuery.makeArray( this.elements ) : this;
		})
		.filter(function(){
			return this.name && !this.disabled &&
				( this.checked || rselectTextarea.test( this.nodeName ) ||
					rinput.test( this.type ) );
		})
		.map(function( i, elem ){
			var val = jQuery( this ).val();

			return val == null ?
				null :
				jQuery.isArray( val ) ?
					jQuery.map( val, function( val, i ){
						return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
					}) :
					{ name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
		}).get();
	}
});

// Attach a bunch of functions for handling common AJAX events
jQuery.each( "ajaxStart ajaxStop ajaxComplete ajaxError ajaxSuccess ajaxSend".split( " " ), function( i, o ){
	jQuery.fn[ o ] = function( f ){
		return this.on( o, f );
	};
});

jQuery.each( [ "get", "post" ], function( i, method ) {
	jQuery[ method ] = function( url, data, callback, type ) {
		// shift arguments if data argument was omitted
		if ( jQuery.isFunction( data ) ) {
			type = type || callback;
			callback = data;
			data = undefined;
		}

		return jQuery.ajax({
			type: method,
			url: url,
			data: data,
			success: callback,
			dataType: type
		});
	};
});

jQuery.extend({

	getScript: function( url, callback ) {
		return jQuery.get( url, undefined, callback, "script" );
	},

	getJSON: function( url, data, callback ) {
		return jQuery.get( url, data, callback, "json" );
	},

	// Creates a full fledged settings object into target
	// with both ajaxSettings and settings fields.
	// If target is omitted, writes into ajaxSettings.
	ajaxSetup: function( target, settings ) {
		if ( settings ) {
			// Building a settings object
			ajaxExtend( target, jQuery.ajaxSettings );
		} else {
			// Extending ajaxSettings
			settings = target;
			target = jQuery.ajaxSettings;
		}
		ajaxExtend( target, settings );
		return target;
	},

	ajaxSettings: {
		url: ajaxLocation,
		isLocal: rlocalProtocol.test( ajaxLocParts[ 1 ] ),
		global: true,
		type: "GET",
		contentType: "application/x-www-form-urlencoded; charset=UTF-8",
		processData: true,
		async: true,
		/*
		timeout: 0,
		data: null,
		dataType: null,
		username: null,
		password: null,
		cache: null,
		traditional: false,
		headers: {},
		*/

		accepts: {
			xml: "application/xml, text/xml",
			html: "text/html",
			text: "text/plain",
			json: "application/json, text/javascript",
			"*": allTypes
		},

		contents: {
			xml: /xml/,
			html: /html/,
			json: /json/
		},

		responseFields: {
			xml: "responseXML",
			text: "responseText"
		},

		// List of data converters
		// 1) key format is "source_type destination_type" (a single space in-between)
		// 2) the catchall symbol "*" can be used for source_type
		converters: {

			// Convert anything to text
			"* text": window.String,

			// Text to html (true = no transformation)
			"text html": true,

			// Evaluate text as a json expression
			"text json": jQuery.parseJSON,

			// Parse text as xml
			"text xml": jQuery.parseXML
		},

		// For options that shouldn't be deep extended:
		// you can add your own custom options here if
		// and when you create one that shouldn't be
		// deep extended (see ajaxExtend)
		flatOptions: {
			context: true,
			url: true
		}
	},

	ajaxPrefilter: addToPrefiltersOrTransports( prefilters ),
	ajaxTransport: addToPrefiltersOrTransports( transports ),

	// Main method
	ajax: function( url, options ) {

		// If url is an object, simulate pre-1.5 signature
		if ( typeof url === "object" ) {
			options = url;
			url = undefined;
		}

		// Force options to be an object
		options = options || {};

		var // Create the final options object
			s = jQuery.ajaxSetup( {}, options ),
			// Callbacks context
			callbackContext = s.context || s,
			// Context for global events
			// It's the callbackContext if one was provided in the options
			// and if it's a DOM node or a jQuery collection
			globalEventContext = callbackContext !== s &&
				( callbackContext.nodeType || callbackContext instanceof jQuery ) ?
						jQuery( callbackContext ) : jQuery.event,
			// Deferreds
			deferred = jQuery.Deferred(),
			completeDeferred = jQuery.Callbacks( "once memory" ),
			// Status-dependent callbacks
			statusCode = s.statusCode || {},
			// ifModified key
			ifModifiedKey,
			// Headers (they are sent all at once)
			requestHeaders = {},
			requestHeadersNames = {},
			// Response headers
			responseHeadersString,
			responseHeaders,
			// transport
			transport,
			// timeout handle
			timeoutTimer,
			// Cross-domain detection vars
			parts,
			// The jqXHR state
			state = 0,
			// To know if global events are to be dispatched
			fireGlobals,
			// Loop variable
			i,
			// Fake xhr
			jqXHR = {

				readyState: 0,

				// Caches the header
				setRequestHeader: function( name, value ) {
					if ( !state ) {
						var lname = name.toLowerCase();
						name = requestHeadersNames[ lname ] = requestHeadersNames[ lname ] || name;
						requestHeaders[ name ] = value;
					}
					return this;
				},

				// Raw string
				getAllResponseHeaders: function() {
					return state === 2 ? responseHeadersString : null;
				},

				// Builds headers hashtable if needed
				getResponseHeader: function( key ) {
					var match;
					if ( state === 2 ) {
						if ( !responseHeaders ) {
							responseHeaders = {};
							while( ( match = rheaders.exec( responseHeadersString ) ) ) {
								responseHeaders[ match[1].toLowerCase() ] = match[ 2 ];
							}
						}
						match = responseHeaders[ key.toLowerCase() ];
					}
					return match === undefined ? null : match;
				},

				// Overrides response content-type header
				overrideMimeType: function( type ) {
					if ( !state ) {
						s.mimeType = type;
					}
					return this;
				},

				// Cancel the request
				abort: function( statusText ) {
					statusText = statusText || "abort";
					if ( transport ) {
						transport.abort( statusText );
					}
					done( 0, statusText );
					return this;
				}
			};

		// Callback for when everything is done
		// It is defined here because jslint complains if it is declared
		// at the end of the function (which would be more logical and readable)
		function done( status, nativeStatusText, responses, headers ) {

			// Called once
			if ( state === 2 ) {
				return;
			}

			// State is "done" now
			state = 2;

			// Clear timeout if it exists
			if ( timeoutTimer ) {
				clearTimeout( timeoutTimer );
			}

			// Dereference transport for early garbage collection
			// (no matter how long the jqXHR object will be used)
			transport = undefined;

			// Cache response headers
			responseHeadersString = headers || "";

			// Set readyState
			jqXHR.readyState = status > 0 ? 4 : 0;

			var isSuccess,
				success,
				error,
				statusText = nativeStatusText,
				response = responses ? ajaxHandleResponses( s, jqXHR, responses ) : undefined,
				lastModified,
				etag;

			// If successful, handle type chaining
			if ( status >= 200 && status < 300 || status === 304 ) {

				// Set the If-Modified-Since and/or If-None-Match header, if in ifModified mode.
				if ( s.ifModified ) {

					if ( ( lastModified = jqXHR.getResponseHeader( "Last-Modified" ) ) ) {
						jQuery.lastModified[ ifModifiedKey ] = lastModified;
					}
					if ( ( etag = jqXHR.getResponseHeader( "Etag" ) ) ) {
						jQuery.etag[ ifModifiedKey ] = etag;
					}
				}

				// If not modified
				if ( status === 304 ) {

					statusText = "notmodified";
					isSuccess = true;

				// If we have data
				} else {

					try {
						success = ajaxConvert( s, response );
						statusText = "success";
						isSuccess = true;
					} catch(e) {
						// We have a parsererror
						statusText = "parsererror";
						error = e;
					}
				}
			} else {
				// We extract error from statusText
				// then normalize statusText and status for non-aborts
				error = statusText;
				if ( !statusText || status ) {
					statusText = "error";
					if ( status < 0 ) {
						status = 0;
					}
				}
			}

			// Set data for the fake xhr object
			jqXHR.status = status;
			jqXHR.statusText = "" + ( nativeStatusText || statusText );

			// Success/Error
			if ( isSuccess ) {
				deferred.resolveWith( callbackContext, [ success, statusText, jqXHR ] );
			} else {
				deferred.rejectWith( callbackContext, [ jqXHR, statusText, error ] );
			}

			// Status-dependent callbacks
			jqXHR.statusCode( statusCode );
			statusCode = undefined;

			if ( fireGlobals ) {
				globalEventContext.trigger( "ajax" + ( isSuccess ? "Success" : "Error" ),
						[ jqXHR, s, isSuccess ? success : error ] );
			}

			// Complete
			completeDeferred.fireWith( callbackContext, [ jqXHR, statusText ] );

			if ( fireGlobals ) {
				globalEventContext.trigger( "ajaxComplete", [ jqXHR, s ] );
				// Handle the global AJAX counter
				if ( !( --jQuery.active ) ) {
					jQuery.event.trigger( "ajaxStop" );
				}
			}
		}

		// Attach deferreds
		deferred.promise( jqXHR );
		jqXHR.success = jqXHR.done;
		jqXHR.error = jqXHR.fail;
		jqXHR.complete = completeDeferred.add;

		// Status-dependent callbacks
		jqXHR.statusCode = function( map ) {
			if ( map ) {
				var tmp;
				if ( state < 2 ) {
					for ( tmp in map ) {
						statusCode[ tmp ] = [ statusCode[tmp], map[tmp] ];
					}
				} else {
					tmp = map[ jqXHR.status ];
					jqXHR.then( tmp, tmp );
				}
			}
			return this;
		};

		// Remove hash character (#7531: and string promotion)
		// Add protocol if not provided (#5866: IE7 issue with protocol-less urls)
		// We also use the url parameter if available
		s.url = ( ( url || s.url ) + "" ).replace( rhash, "" ).replace( rprotocol, ajaxLocParts[ 1 ] + "//" );

		// Extract dataTypes list
		s.dataTypes = jQuery.trim( s.dataType || "*" ).toLowerCase().split( rspacesAjax );

		// Determine if a cross-domain request is in order
		if ( s.crossDomain == null ) {
			parts = rurl.exec( s.url.toLowerCase() );
			s.crossDomain = !!( parts &&
				( parts[ 1 ] != ajaxLocParts[ 1 ] || parts[ 2 ] != ajaxLocParts[ 2 ] ||
					( parts[ 3 ] || ( parts[ 1 ] === "http:" ? 80 : 443 ) ) !=
						( ajaxLocParts[ 3 ] || ( ajaxLocParts[ 1 ] === "http:" ? 80 : 443 ) ) )
			);
		}

		// Convert data if not already a string
		if ( s.data && s.processData && typeof s.data !== "string" ) {
			s.data = jQuery.param( s.data, s.traditional );
		}

		// Apply prefilters
		inspectPrefiltersOrTransports( prefilters, s, options, jqXHR );

		// If request was aborted inside a prefilter, stop there
		if ( state === 2 ) {
			return false;
		}

		// We can fire global events as of now if asked to
		fireGlobals = s.global;

		// Uppercase the type
		s.type = s.type.toUpperCase();

		// Determine if request has content
		s.hasContent = !rnoContent.test( s.type );

		// Watch for a new set of requests
		if ( fireGlobals && jQuery.active++ === 0 ) {
			jQuery.event.trigger( "ajaxStart" );
		}

		// More options handling for requests with no content
		if ( !s.hasContent ) {

			// If data is available, append data to url
			if ( s.data ) {
				s.url += ( rquery.test( s.url ) ? "&" : "?" ) + s.data;
				// #9682: remove data so that it's not used in an eventual retry
				delete s.data;
			}

			// Get ifModifiedKey before adding the anti-cache parameter
			ifModifiedKey = s.url;

			// Add anti-cache in url if needed
			if ( s.cache === false ) {

				var ts = jQuery.now(),
					// try replacing _= if it is there
					ret = s.url.replace( rts, "$1_=" + ts );

				// if nothing was replaced, add timestamp to the end
				s.url = ret + ( ( ret === s.url ) ? ( rquery.test( s.url ) ? "&" : "?" ) + "_=" + ts : "" );
			}
		}

		// Set the correct header, if data is being sent
		if ( s.data && s.hasContent && s.contentType !== false || options.contentType ) {
			jqXHR.setRequestHeader( "Content-Type", s.contentType );
		}

		// Set the If-Modified-Since and/or If-None-Match header, if in ifModified mode.
		if ( s.ifModified ) {
			ifModifiedKey = ifModifiedKey || s.url;
			if ( jQuery.lastModified[ ifModifiedKey ] ) {
				jqXHR.setRequestHeader( "If-Modified-Since", jQuery.lastModified[ ifModifiedKey ] );
			}
			if ( jQuery.etag[ ifModifiedKey ] ) {
				jqXHR.setRequestHeader( "If-None-Match", jQuery.etag[ ifModifiedKey ] );
			}
		}

		// Set the Accepts header for the server, depending on the dataType
		jqXHR.setRequestHeader(
			"Accept",
			s.dataTypes[ 0 ] && s.accepts[ s.dataTypes[0] ] ?
				s.accepts[ s.dataTypes[0] ] + ( s.dataTypes[ 0 ] !== "*" ? ", " + allTypes + "; q=0.01" : "" ) :
				s.accepts[ "*" ]
		);

		// Check for headers option
		for ( i in s.headers ) {
			jqXHR.setRequestHeader( i, s.headers[ i ] );
		}

		// Allow custom headers/mimetypes and early abort
		if ( s.beforeSend && ( s.beforeSend.call( callbackContext, jqXHR, s ) === false || state === 2 ) ) {
				// Abort if not done already
				jqXHR.abort();
				return false;

		}

		// Install callbacks on deferreds
		for ( i in { success: 1, error: 1, complete: 1 } ) {
			jqXHR[ i ]( s[ i ] );
		}

		// Get transport
		transport = inspectPrefiltersOrTransports( transports, s, options, jqXHR );

		// If no transport, we auto-abort
		if ( !transport ) {
			done( -1, "No Transport" );
		} else {
			jqXHR.readyState = 1;
			// Send global event
			if ( fireGlobals ) {
				globalEventContext.trigger( "ajaxSend", [ jqXHR, s ] );
			}
			// Timeout
			if ( s.async && s.timeout > 0 ) {
				timeoutTimer = setTimeout( function(){
					jqXHR.abort( "timeout" );
				}, s.timeout );
			}

			try {
				state = 1;
				transport.send( requestHeaders, done );
			} catch (e) {
				// Propagate exception as error if not done
				if ( state < 2 ) {
					done( -1, e );
				// Simply rethrow otherwise
				} else {
					throw e;
				}
			}
		}

		return jqXHR;
	},

	// Serialize an array of form elements or a set of
	// key/values into a query string
	param: function( a, traditional ) {
		var s = [],
			add = function( key, value ) {
				// If value is a function, invoke it and return its value
				value = jQuery.isFunction( value ) ? value() : value;
				s[ s.length ] = encodeURIComponent( key ) + "=" + encodeURIComponent( value );
			};

		// Set traditional to true for jQuery <= 1.3.2 behavior.
		if ( traditional === undefined ) {
			traditional = jQuery.ajaxSettings.traditional;
		}

		// If an array was passed in, assume that it is an array of form elements.
		if ( jQuery.isArray( a ) || ( a.jquery && !jQuery.isPlainObject( a ) ) ) {
			// Serialize the form elements
			jQuery.each( a, function() {
				add( this.name, this.value );
			});

		} else {
			// If traditional, encode the "old" way (the way 1.3.2 or older
			// did it), otherwise encode params recursively.
			for ( var prefix in a ) {
				buildParams( prefix, a[ prefix ], traditional, add );
			}
		}

		// Return the resulting serialization
		return s.join( "&" ).replace( r20, "+" );
	}
});

function buildParams( prefix, obj, traditional, add ) {
	if ( jQuery.isArray( obj ) ) {
		// Serialize array item.
		jQuery.each( obj, function( i, v ) {
			if ( traditional || rbracket.test( prefix ) ) {
				// Treat each array item as a scalar.
				add( prefix, v );

			} else {
				// If array item is non-scalar (array or object), encode its
				// numeric index to resolve deserialization ambiguity issues.
				// Note that rack (as of 1.0.0) can't currently deserialize
				// nested arrays properly, and attempting to do so may cause
				// a server error. Possible fixes are to modify rack's
				// deserialization algorithm or to provide an option or flag
				// to force array serialization to be shallow.
				buildParams( prefix + "[" + ( typeof v === "object" ? i : "" ) + "]", v, traditional, add );
			}
		});

	} else if ( !traditional && jQuery.type( obj ) === "object" ) {
		// Serialize object item.
		for ( var name in obj ) {
			buildParams( prefix + "[" + name + "]", obj[ name ], traditional, add );
		}

	} else {
		// Serialize scalar item.
		add( prefix, obj );
	}
}

// This is still on the jQuery object... for now
// Want to move this to jQuery.ajax some day
jQuery.extend({

	// Counter for holding the number of active queries
	active: 0,

	// Last-Modified header cache for next request
	lastModified: {},
	etag: {}

});

/* Handles responses to an ajax request:
 * - sets all responseXXX fields accordingly
 * - finds the right dataType (mediates between content-type and expected dataType)
 * - returns the corresponding response
 */
function ajaxHandleResponses( s, jqXHR, responses ) {

	var contents = s.contents,
		dataTypes = s.dataTypes,
		responseFields = s.responseFields,
		ct,
		type,
		finalDataType,
		firstDataType;

	// Fill responseXXX fields
	for ( type in responseFields ) {
		if ( type in responses ) {
			jqXHR[ responseFields[type] ] = responses[ type ];
		}
	}

	// Remove auto dataType and get content-type in the process
	while( dataTypes[ 0 ] === "*" ) {
		dataTypes.shift();
		if ( ct === undefined ) {
			ct = s.mimeType || jqXHR.getResponseHeader( "content-type" );
		}
	}

	// Check if we're dealing with a known content-type
	if ( ct ) {
		for ( type in contents ) {
			if ( contents[ type ] && contents[ type ].test( ct ) ) {
				dataTypes.unshift( type );
				break;
			}
		}
	}

	// Check to see if we have a response for the expected dataType
	if ( dataTypes[ 0 ] in responses ) {
		finalDataType = dataTypes[ 0 ];
	} else {
		// Try convertible dataTypes
		for ( type in responses ) {
			if ( !dataTypes[ 0 ] || s.converters[ type + " " + dataTypes[0] ] ) {
				finalDataType = type;
				break;
			}
			if ( !firstDataType ) {
				firstDataType = type;
			}
		}
		// Or just use first one
		finalDataType = finalDataType || firstDataType;
	}

	// If we found a dataType
	// We add the dataType to the list if needed
	// and return the corresponding response
	if ( finalDataType ) {
		if ( finalDataType !== dataTypes[ 0 ] ) {
			dataTypes.unshift( finalDataType );
		}
		return responses[ finalDataType ];
	}
}

// Chain conversions given the request and the original response
function ajaxConvert( s, response ) {

	// Apply the dataFilter if provided
	if ( s.dataFilter ) {
		response = s.dataFilter( response, s.dataType );
	}

	var dataTypes = s.dataTypes,
		converters = {},
		i,
		key,
		length = dataTypes.length,
		tmp,
		// Current and previous dataTypes
		current = dataTypes[ 0 ],
		prev,
		// Conversion expression
		conversion,
		// Conversion function
		conv,
		// Conversion functions (transitive conversion)
		conv1,
		conv2;

	// For each dataType in the chain
	for ( i = 1; i < length; i++ ) {

		// Create converters map
		// with lowercased keys
		if ( i === 1 ) {
			for ( key in s.converters ) {
				if ( typeof key === "string" ) {
					converters[ key.toLowerCase() ] = s.converters[ key ];
				}
			}
		}

		// Get the dataTypes
		prev = current;
		current = dataTypes[ i ];

		// If current is auto dataType, update it to prev
		if ( current === "*" ) {
			current = prev;
		// If no auto and dataTypes are actually different
		} else if ( prev !== "*" && prev !== current ) {

			// Get the converter
			conversion = prev + " " + current;
			conv = converters[ conversion ] || converters[ "* " + current ];

			// If there is no direct converter, search transitively
			if ( !conv ) {
				conv2 = undefined;
				for ( conv1 in converters ) {
					tmp = conv1.split( " " );
					if ( tmp[ 0 ] === prev || tmp[ 0 ] === "*" ) {
						conv2 = converters[ tmp[1] + " " + current ];
						if ( conv2 ) {
							conv1 = converters[ conv1 ];
							if ( conv1 === true ) {
								conv = conv2;
							} else if ( conv2 === true ) {
								conv = conv1;
							}
							break;
						}
					}
				}
			}
			// If we found no converter, dispatch an error
			if ( !( conv || conv2 ) ) {
				jQuery.error( "No conversion from " + conversion.replace(" "," to ") );
			}
			// If found converter is not an equivalence
			if ( conv !== true ) {
				// Convert with 1 or 2 converters accordingly
				response = conv ? conv( response ) : conv2( conv1(response) );
			}
		}
	}
	return response;
}




var jsc = jQuery.now(),
	jsre = /(\=)\?(&|$)|\?\?/i;

// Default jsonp settings
jQuery.ajaxSetup({
	jsonp: "callback",
	jsonpCallback: function() {
		return jQuery.expando + "_" + ( jsc++ );
	}
});

// Detect, normalize options and install callbacks for jsonp requests
jQuery.ajaxPrefilter( "json jsonp", function( s, originalSettings, jqXHR ) {

	var inspectData = ( typeof s.data === "string" ) && /^application\/x\-www\-form\-urlencoded/.test( s.contentType );

	if ( s.dataTypes[ 0 ] === "jsonp" ||
		s.jsonp !== false && ( jsre.test( s.url ) ||
				inspectData && jsre.test( s.data ) ) ) {

		var responseContainer,
			jsonpCallback = s.jsonpCallback =
				jQuery.isFunction( s.jsonpCallback ) ? s.jsonpCallback() : s.jsonpCallback,
			previous = window[ jsonpCallback ],
			url = s.url,
			data = s.data,
			replace = "$1" + jsonpCallback + "$2";

		if ( s.jsonp !== false ) {
			url = url.replace( jsre, replace );
			if ( s.url === url ) {
				if ( inspectData ) {
					data = data.replace( jsre, replace );
				}
				if ( s.data === data ) {
					// Add callback manually
					url += (/\?/.test( url ) ? "&" : "?") + s.jsonp + "=" + jsonpCallback;
				}
			}
		}

		s.url = url;
		s.data = data;

		// Install callback
		window[ jsonpCallback ] = function( response ) {
			responseContainer = [ response ];
		};

		// Clean-up function
		jqXHR.always(function() {
			// Set callback back to previous value
			window[ jsonpCallback ] = previous;
			// Call if it was a function and we have a response
			if ( responseContainer && jQuery.isFunction( previous ) ) {
				window[ jsonpCallback ]( responseContainer[ 0 ] );
			}
		});

		// Use data converter to retrieve json after script execution
		s.converters["script json"] = function() {
			if ( !responseContainer ) {
				jQuery.error( jsonpCallback + " was not called" );
			}
			return responseContainer[ 0 ];
		};

		// force json dataType
		s.dataTypes[ 0 ] = "json";

		// Delegate to script
		return "script";
	}
});




// Install script dataType
jQuery.ajaxSetup({
	accepts: {
		script: "text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"
	},
	contents: {
		script: /javascript|ecmascript/
	},
	converters: {
		"text script": function( text ) {
			jQuery.globalEval( text );
			return text;
		}
	}
});

// Handle cache's special case and global
jQuery.ajaxPrefilter( "script", function( s ) {
	if ( s.cache === undefined ) {
		s.cache = false;
	}
	if ( s.crossDomain ) {
		s.type = "GET";
		s.global = false;
	}
});

// Bind script tag hack transport
jQuery.ajaxTransport( "script", function(s) {

	// This transport only deals with cross domain requests
	if ( s.crossDomain ) {

		var script,
			head = document.head || document.getElementsByTagName( "head" )[0] || document.documentElement;

		return {

			send: function( _, callback ) {

				script = document.createElement( "script" );

				script.async = "async";

				if ( s.scriptCharset ) {
					script.charset = s.scriptCharset;
				}

				script.src = s.url;

				// Attach handlers for all browsers
				script.onload = script.onreadystatechange = function( _, isAbort ) {

					if ( isAbort || !script.readyState || /loaded|complete/.test( script.readyState ) ) {

						// Handle memory leak in IE
						script.onload = script.onreadystatechange = null;

						// Remove the script
						if ( head && script.parentNode ) {
							head.removeChild( script );
						}

						// Dereference the script
						script = undefined;

						// Callback if not abort
						if ( !isAbort ) {
							callback( 200, "success" );
						}
					}
				};
				// Use insertBefore instead of appendChild  to circumvent an IE6 bug.
				// This arises when a base node is used (#2709 and #4378).
				head.insertBefore( script, head.firstChild );
			},

			abort: function() {
				if ( script ) {
					script.onload( 0, 1 );
				}
			}
		};
	}
});




var // #5280: Internet Explorer will keep connections alive if we don't abort on unload
	xhrOnUnloadAbort = window.ActiveXObject ? function() {
		// Abort all pending requests
		for ( var key in xhrCallbacks ) {
			xhrCallbacks[ key ]( 0, 1 );
		}
	} : false,
	xhrId = 0,
	xhrCallbacks;

// Functions to create xhrs
function createStandardXHR() {
	try {
		return new window.XMLHttpRequest();
	} catch( e ) {}
}

function createActiveXHR() {
	try {
		return new window.ActiveXObject( "Microsoft.XMLHTTP" );
	} catch( e ) {}
}

// Create the request object
// (This is still attached to ajaxSettings for backward compatibility)
jQuery.ajaxSettings.xhr = window.ActiveXObject ?
	/* Microsoft failed to properly
	 * implement the XMLHttpRequest in IE7 (can't request local files),
	 * so we use the ActiveXObject when it is available
	 * Additionally XMLHttpRequest can be disabled in IE7/IE8 so
	 * we need a fallback.
	 */
	function() {
		return !this.isLocal && createStandardXHR() || createActiveXHR();
	} :
	// For all other browsers, use the standard XMLHttpRequest object
	createStandardXHR;

// Determine support properties
(function( xhr ) {
	jQuery.extend( jQuery.support, {
		ajax: !!xhr,
		cors: !!xhr && ( "withCredentials" in xhr )
	});
})( jQuery.ajaxSettings.xhr() );

// Create transport if the browser can provide an xhr
if ( jQuery.support.ajax ) {

	jQuery.ajaxTransport(function( s ) {
		// Cross domain only allowed if supported through XMLHttpRequest
		if ( !s.crossDomain || jQuery.support.cors ) {

			var callback;

			return {
				send: function( headers, complete ) {

					// Get a new xhr
					var xhr = s.xhr(),
						handle,
						i;

					// Open the socket
					// Passing null username, generates a login popup on Opera (#2865)
					if ( s.username ) {
						xhr.open( s.type, s.url, s.async, s.username, s.password );
					} else {
						xhr.open( s.type, s.url, s.async );
					}

					// Apply custom fields if provided
					if ( s.xhrFields ) {
						for ( i in s.xhrFields ) {
							xhr[ i ] = s.xhrFields[ i ];
						}
					}

					// Override mime type if needed
					if ( s.mimeType && xhr.overrideMimeType ) {
						xhr.overrideMimeType( s.mimeType );
					}

					// X-Requested-With header
					// For cross-domain requests, seeing as conditions for a preflight are
					// akin to a jigsaw puzzle, we simply never set it to be sure.
					// (it can always be set on a per-request basis or even using ajaxSetup)
					// For same-domain requests, won't change header if already provided.
					if ( !s.crossDomain && !headers["X-Requested-With"] ) {
						headers[ "X-Requested-With" ] = "XMLHttpRequest";
					}

					// Need an extra try/catch for cross domain requests in Firefox 3
					try {
						for ( i in headers ) {
							xhr.setRequestHeader( i, headers[ i ] );
						}
					} catch( _ ) {}

					// Do send the request
					// This may raise an exception which is actually
					// handled in jQuery.ajax (so no try/catch here)
					xhr.send( ( s.hasContent && s.data ) || null );

					// Listener
					callback = function( _, isAbort ) {

						var status,
							statusText,
							responseHeaders,
							responses,
							xml;

						// Firefox throws exceptions when accessing properties
						// of an xhr when a network error occured
						// http://helpful.knobs-dials.com/index.php/Component_returned_failure_code:_0x80040111_(NS_ERROR_NOT_AVAILABLE)
						try {

							// Was never called and is aborted or complete
							if ( callback && ( isAbort || xhr.readyState === 4 ) ) {

								// Only called once
								callback = undefined;

								// Do not keep as active anymore
								if ( handle ) {
									xhr.onreadystatechange = jQuery.noop;
									if ( xhrOnUnloadAbort ) {
										delete xhrCallbacks[ handle ];
									}
								}

								// If it's an abort
								if ( isAbort ) {
									// Abort it manually if needed
									if ( xhr.readyState !== 4 ) {
										xhr.abort();
									}
								} else {
									status = xhr.status;
									responseHeaders = xhr.getAllResponseHeaders();
									responses = {};
									xml = xhr.responseXML;

									// Construct response list
									if ( xml && xml.documentElement /* #4958 */ ) {
										responses.xml = xml;
									}

									// When requesting binary data, IE6-9 will throw an exception
									// on any attempt to access responseText (#11426)
									try {
										responses.text = xhr.responseText;
									} catch( _ ) {
									}

									// Firefox throws an exception when accessing
									// statusText for faulty cross-domain requests
									try {
										statusText = xhr.statusText;
									} catch( e ) {
										// We normalize with Webkit giving an empty statusText
										statusText = "";
									}

									// Filter status for non standard behaviors

									// If the request is local and we have data: assume a success
									// (success with no data won't get notified, that's the best we
									// can do given current implementations)
									if ( !status && s.isLocal && !s.crossDomain ) {
										status = responses.text ? 200 : 404;
									// IE - #1450: sometimes returns 1223 when it should be 204
									} else if ( status === 1223 ) {
										status = 204;
									}
								}
							}
						} catch( firefoxAccessException ) {
							if ( !isAbort ) {
								complete( -1, firefoxAccessException );
							}
						}

						// Call complete if needed
						if ( responses ) {
							complete( status, statusText, responses, responseHeaders );
						}
					};

					// if we're in sync mode or it's in cache
					// and has been retrieved directly (IE6 & IE7)
					// we need to manually fire the callback
					if ( !s.async || xhr.readyState === 4 ) {
						callback();
					} else {
						handle = ++xhrId;
						if ( xhrOnUnloadAbort ) {
							// Create the active xhrs callbacks list if needed
							// and attach the unload handler
							if ( !xhrCallbacks ) {
								xhrCallbacks = {};
								jQuery( window ).unload( xhrOnUnloadAbort );
							}
							// Add to list of active xhrs callbacks
							xhrCallbacks[ handle ] = callback;
						}
						xhr.onreadystatechange = callback;
					}
				},

				abort: function() {
					if ( callback ) {
						callback(0,1);
					}
				}
			};
		}
	});
}




var elemdisplay = {},
	iframe, iframeDoc,
	rfxtypes = /^(?:toggle|show|hide)$/,
	rfxnum = /^([+\-]=)?([\d+.\-]+)([a-z%]*)$/i,
	timerId,
	fxAttrs = [
		// height animations
		[ "height", "marginTop", "marginBottom", "paddingTop", "paddingBottom" ],
		// width animations
		[ "width", "marginLeft", "marginRight", "paddingLeft", "paddingRight" ],
		// opacity animations
		[ "opacity" ]
	],
	fxNow;

jQuery.fn.extend({
	show: function( speed, easing, callback ) {
		var elem, display;

		if ( speed || speed === 0 ) {
			return this.animate( genFx("show", 3), speed, easing, callback );

		} else {
			for ( var i = 0, j = this.length; i < j; i++ ) {
				elem = this[ i ];

				if ( elem.style ) {
					display = elem.style.display;

					// Reset the inline display of this element to learn if it is
					// being hidden by cascaded rules or not
					if ( !jQuery._data(elem, "olddisplay") && display === "none" ) {
						display = elem.style.display = "";
					}

					// Set elements which have been overridden with display: none
					// in a stylesheet to whatever the default browser style is
					// for such an element
					if ( (display === "" && jQuery.css(elem, "display") === "none") ||
						!jQuery.contains( elem.ownerDocument.documentElement, elem ) ) {
						jQuery._data( elem, "olddisplay", defaultDisplay(elem.nodeName) );
					}
				}
			}

			// Set the display of most of the elements in a second loop
			// to avoid the constant reflow
			for ( i = 0; i < j; i++ ) {
				elem = this[ i ];

				if ( elem.style ) {
					display = elem.style.display;

					if ( display === "" || display === "none" ) {
						elem.style.display = jQuery._data( elem, "olddisplay" ) || "";
					}
				}
			}

			return this;
		}
	},

	hide: function( speed, easing, callback ) {
		if ( speed || speed === 0 ) {
			return this.animate( genFx("hide", 3), speed, easing, callback);

		} else {
			var elem, display,
				i = 0,
				j = this.length;

			for ( ; i < j; i++ ) {
				elem = this[i];
				if ( elem.style ) {
					display = jQuery.css( elem, "display" );

					if ( display !== "none" && !jQuery._data( elem, "olddisplay" ) ) {
						jQuery._data( elem, "olddisplay", display );
					}
				}
			}

			// Set the display of the elements in a second loop
			// to avoid the constant reflow
			for ( i = 0; i < j; i++ ) {
				if ( this[i].style ) {
					this[i].style.display = "none";
				}
			}

			return this;
		}
	},

	// Save the old toggle function
	_toggle: jQuery.fn.toggle,

	toggle: function( fn, fn2, callback ) {
		var bool = typeof fn === "boolean";

		if ( jQuery.isFunction(fn) && jQuery.isFunction(fn2) ) {
			this._toggle.apply( this, arguments );

		} else if ( fn == null || bool ) {
			this.each(function() {
				var state = bool ? fn : jQuery(this).is(":hidden");
				jQuery(this)[ state ? "show" : "hide" ]();
			});

		} else {
			this.animate(genFx("toggle", 3), fn, fn2, callback);
		}

		return this;
	},

	fadeTo: function( speed, to, easing, callback ) {
		return this.filter(":hidden").css("opacity", 0).show().end()
					.animate({opacity: to}, speed, easing, callback);
	},

	animate: function( prop, speed, easing, callback ) {
		var optall = jQuery.speed( speed, easing, callback );

		if ( jQuery.isEmptyObject( prop ) ) {
			return this.each( optall.complete, [ false ] );
		}

		// Do not change referenced properties as per-property easing will be lost
		prop = jQuery.extend( {}, prop );

		function doAnimation() {
			// XXX 'this' does not always have a nodeName when running the
			// test suite

			if ( optall.queue === false ) {
				jQuery._mark( this );
			}

			var opt = jQuery.extend( {}, optall ),
				isElement = this.nodeType === 1,
				hidden = isElement && jQuery(this).is(":hidden"),
				name, val, p, e, hooks, replace,
				parts, start, end, unit,
				method;

			// will store per property easing and be used to determine when an animation is complete
			opt.animatedProperties = {};

			// first pass over propertys to expand / normalize
			for ( p in prop ) {
				name = jQuery.camelCase( p );
				if ( p !== name ) {
					prop[ name ] = prop[ p ];
					delete prop[ p ];
				}

				if ( ( hooks = jQuery.cssHooks[ name ] ) && "expand" in hooks ) {
					replace = hooks.expand( prop[ name ] );
					delete prop[ name ];

					// not quite $.extend, this wont overwrite keys already present.
					// also - reusing 'p' from above because we have the correct "name"
					for ( p in replace ) {
						if ( ! ( p in prop ) ) {
							prop[ p ] = replace[ p ];
						}
					}
				}
			}

			for ( name in prop ) {
				val = prop[ name ];
				// easing resolution: per property > opt.specialEasing > opt.easing > 'swing' (default)
				if ( jQuery.isArray( val ) ) {
					opt.animatedProperties[ name ] = val[ 1 ];
					val = prop[ name ] = val[ 0 ];
				} else {
					opt.animatedProperties[ name ] = opt.specialEasing && opt.specialEasing[ name ] || opt.easing || 'swing';
				}

				if ( val === "hide" && hidden || val === "show" && !hidden ) {
					return opt.complete.call( this );
				}

				if ( isElement && ( name === "height" || name === "width" ) ) {
					// Make sure that nothing sneaks out
					// Record all 3 overflow attributes because IE does not
					// change the overflow attribute when overflowX and
					// overflowY are set to the same value
					opt.overflow = [ this.style.overflow, this.style.overflowX, this.style.overflowY ];

					// Set display property to inline-block for height/width
					// animations on inline elements that are having width/height animated
					if ( jQuery.css( this, "display" ) === "inline" &&
							jQuery.css( this, "float" ) === "none" ) {

						// inline-level elements accept inline-block;
						// block-level elements need to be inline with layout
						if ( !jQuery.support.inlineBlockNeedsLayout || defaultDisplay( this.nodeName ) === "inline" ) {
							this.style.display = "inline-block";

						} else {
							this.style.zoom = 1;
						}
					}
				}
			}

			if ( opt.overflow != null ) {
				this.style.overflow = "hidden";
			}

			for ( p in prop ) {
				e = new jQuery.fx( this, opt, p );
				val = prop[ p ];

				if ( rfxtypes.test( val ) ) {

					// Tracks whether to show or hide based on private
					// data attached to the element
					method = jQuery._data( this, "toggle" + p ) || ( val === "toggle" ? hidden ? "show" : "hide" : 0 );
					if ( method ) {
						jQuery._data( this, "toggle" + p, method === "show" ? "hide" : "show" );
						e[ method ]();
					} else {
						e[ val ]();
					}

				} else {
					parts = rfxnum.exec( val.toString() );
					start = e.cur();

					if ( parts ) {
						end = parseFloat( parts[2] );
						unit = parts[3] || ( jQuery.cssNumber[ p ] ? "" : "px" );

						// We need to compute starting value
						if ( unit !== "px" ) {
							jQuery.style( this, p, (end || 1) + unit);
							start = ( (end || 1) / e.cur() ) * start;
							jQuery.style( this, p, start + unit);
						}

						// If a +=/-= token was provided, we're doing a relative animation
						if ( parts[1] ) {
							end = ( (parts[ 1 ] === "-=" ? -1 : 1) * end ) + start;
						}

						e.custom( start, end, unit );

					} else {
						e.custom( start, val, "" );
					}
				}
			}

			// For JS strict compliance
			return true;
		}

		return optall.queue === false ?
			this.each( doAnimation ) :
			this.queue( optall.queue, doAnimation );
	},

	stop: function( type, clearQueue, gotoEnd ) {
		if ( typeof type !== "string" ) {
			gotoEnd = clearQueue;
			clearQueue = type;
			type = undefined;
		}
		if ( clearQueue && type !== false ) {
			this.queue( type || "fx", [] );
		}

		return this.each(function() {
			var index,
				hadTimers = false,
				timers = jQuery.timers,
				data = jQuery._data( this );

			// clear marker counters if we know they won't be
			if ( !gotoEnd ) {
				jQuery._unmark( true, this );
			}

			function stopQueue( elem, data, index ) {
				var hooks = data[ index ];
				jQuery.removeData( elem, index, true );
				hooks.stop( gotoEnd );
			}

			if ( type == null ) {
				for ( index in data ) {
					if ( data[ index ] && data[ index ].stop && index.indexOf(".run") === index.length - 4 ) {
						stopQueue( this, data, index );
					}
				}
			} else if ( data[ index = type + ".run" ] && data[ index ].stop ){
				stopQueue( this, data, index );
			}

			for ( index = timers.length; index--; ) {
				if ( timers[ index ].elem === this && (type == null || timers[ index ].queue === type) ) {
					if ( gotoEnd ) {

						// force the next step to be the last
						timers[ index ]( true );
					} else {
						timers[ index ].saveState();
					}
					hadTimers = true;
					timers.splice( index, 1 );
				}
			}

			// start the next in the queue if the last step wasn't forced
			// timers currently will call their complete callbacks, which will dequeue
			// but only if they were gotoEnd
			if ( !( gotoEnd && hadTimers ) ) {
				jQuery.dequeue( this, type );
			}
		});
	}

});

// Animations created synchronously will run synchronously
function createFxNow() {
	setTimeout( clearFxNow, 0 );
	return ( fxNow = jQuery.now() );
}

function clearFxNow() {
	fxNow = undefined;
}

// Generate parameters to create a standard animation
function genFx( type, num ) {
	var obj = {};

	jQuery.each( fxAttrs.concat.apply([], fxAttrs.slice( 0, num )), function() {
		obj[ this ] = type;
	});

	return obj;
}

// Generate shortcuts for custom animations
jQuery.each({
	slideDown: genFx( "show", 1 ),
	slideUp: genFx( "hide", 1 ),
	slideToggle: genFx( "toggle", 1 ),
	fadeIn: { opacity: "show" },
	fadeOut: { opacity: "hide" },
	fadeToggle: { opacity: "toggle" }
}, function( name, props ) {
	jQuery.fn[ name ] = function( speed, easing, callback ) {
		return this.animate( props, speed, easing, callback );
	};
});

jQuery.extend({
	speed: function( speed, easing, fn ) {
		var opt = speed && typeof speed === "object" ? jQuery.extend( {}, speed ) : {
			complete: fn || !fn && easing ||
				jQuery.isFunction( speed ) && speed,
			duration: speed,
			easing: fn && easing || easing && !jQuery.isFunction( easing ) && easing
		};

		opt.duration = jQuery.fx.off ? 0 : typeof opt.duration === "number" ? opt.duration :
			opt.duration in jQuery.fx.speeds ? jQuery.fx.speeds[ opt.duration ] : jQuery.fx.speeds._default;

		// normalize opt.queue - true/undefined/null -> "fx"
		if ( opt.queue == null || opt.queue === true ) {
			opt.queue = "fx";
		}

		// Queueing
		opt.old = opt.complete;

		opt.complete = function( noUnmark ) {
			if ( jQuery.isFunction( opt.old ) ) {
				opt.old.call( this );
			}

			if ( opt.queue ) {
				jQuery.dequeue( this, opt.queue );
			} else if ( noUnmark !== false ) {
				jQuery._unmark( this );
			}
		};

		return opt;
	},

	easing: {
		linear: function( p ) {
			return p;
		},
		swing: function( p ) {
			return ( -Math.cos( p*Math.PI ) / 2 ) + 0.5;
		}
	},

	timers: [],

	fx: function( elem, options, prop ) {
		this.options = options;
		this.elem = elem;
		this.prop = prop;

		options.orig = options.orig || {};
	}

});

jQuery.fx.prototype = {
	// Simple function for setting a style value
	update: function() {
		if ( this.options.step ) {
			this.options.step.call( this.elem, this.now, this );
		}

		( jQuery.fx.step[ this.prop ] || jQuery.fx.step._default )( this );
	},

	// Get the current size
	cur: function() {
		if ( this.elem[ this.prop ] != null && (!this.elem.style || this.elem.style[ this.prop ] == null) ) {
			return this.elem[ this.prop ];
		}

		var parsed,
			r = jQuery.css( this.elem, this.prop );
		// Empty strings, null, undefined and "auto" are converted to 0,
		// complex values such as "rotate(1rad)" are returned as is,
		// simple values such as "10px" are parsed to Float.
		return isNaN( parsed = parseFloat( r ) ) ? !r || r === "auto" ? 0 : r : parsed;
	},

	// Start an animation from one number to another
	custom: function( from, to, unit ) {
		var self = this,
			fx = jQuery.fx;

		this.startTime = fxNow || createFxNow();
		this.end = to;
		this.now = this.start = from;
		this.pos = this.state = 0;
		this.unit = unit || this.unit || ( jQuery.cssNumber[ this.prop ] ? "" : "px" );

		function t( gotoEnd ) {
			return self.step( gotoEnd );
		}

		t.queue = this.options.queue;
		t.elem = this.elem;
		t.saveState = function() {
			if ( jQuery._data( self.elem, "fxshow" + self.prop ) === undefined ) {
				if ( self.options.hide ) {
					jQuery._data( self.elem, "fxshow" + self.prop, self.start );
				} else if ( self.options.show ) {
					jQuery._data( self.elem, "fxshow" + self.prop, self.end );
				}
			}
		};

		if ( t() && jQuery.timers.push(t) && !timerId ) {
			timerId = setInterval( fx.tick, fx.interval );
		}
	},

	// Simple 'show' function
	show: function() {
		var dataShow = jQuery._data( this.elem, "fxshow" + this.prop );

		// Remember where we started, so that we can go back to it later
		this.options.orig[ this.prop ] = dataShow || jQuery.style( this.elem, this.prop );
		this.options.show = true;

		// Begin the animation
		// Make sure that we start at a small width/height to avoid any flash of content
		if ( dataShow !== undefined ) {
			// This show is picking up where a previous hide or show left off
			this.custom( this.cur(), dataShow );
		} else {
			this.custom( this.prop === "width" || this.prop === "height" ? 1 : 0, this.cur() );
		}

		// Start by showing the element
		jQuery( this.elem ).show();
	},

	// Simple 'hide' function
	hide: function() {
		// Remember where we started, so that we can go back to it later
		this.options.orig[ this.prop ] = jQuery._data( this.elem, "fxshow" + this.prop ) || jQuery.style( this.elem, this.prop );
		this.options.hide = true;

		// Begin the animation
		this.custom( this.cur(), 0 );
	},

	// Each step of an animation
	step: function( gotoEnd ) {
		var p, n, complete,
			t = fxNow || createFxNow(),
			done = true,
			elem = this.elem,
			options = this.options;

		if ( gotoEnd || t >= options.duration + this.startTime ) {
			this.now = this.end;
			this.pos = this.state = 1;
			this.update();

			options.animatedProperties[ this.prop ] = true;

			for ( p in options.animatedProperties ) {
				if ( options.animatedProperties[ p ] !== true ) {
					done = false;
				}
			}

			if ( done ) {
				// Reset the overflow
				if ( options.overflow != null && !jQuery.support.shrinkWrapBlocks ) {

					jQuery.each( [ "", "X", "Y" ], function( index, value ) {
						elem.style[ "overflow" + value ] = options.overflow[ index ];
					});
				}

				// Hide the element if the "hide" operation was done
				if ( options.hide ) {
					jQuery( elem ).hide();
				}

				// Reset the properties, if the item has been hidden or shown
				if ( options.hide || options.show ) {
					for ( p in options.animatedProperties ) {
						jQuery.style( elem, p, options.orig[ p ] );
						jQuery.removeData( elem, "fxshow" + p, true );
						// Toggle data is no longer needed
						jQuery.removeData( elem, "toggle" + p, true );
					}
				}

				// Execute the complete function
				// in the event that the complete function throws an exception
				// we must ensure it won't be called twice. #5684

				complete = options.complete;
				if ( complete ) {

					options.complete = false;
					complete.call( elem );
				}
			}

			return false;

		} else {
			// classical easing cannot be used with an Infinity duration
			if ( options.duration == Infinity ) {
				this.now = t;
			} else {
				n = t - this.startTime;
				this.state = n / options.duration;

				// Perform the easing function, defaults to swing
				this.pos = jQuery.easing[ options.animatedProperties[this.prop] ]( this.state, n, 0, 1, options.duration );
				this.now = this.start + ( (this.end - this.start) * this.pos );
			}
			// Perform the next step of the animation
			this.update();
		}

		return true;
	}
};

jQuery.extend( jQuery.fx, {
	tick: function() {
		var timer,
			timers = jQuery.timers,
			i = 0;

		for ( ; i < timers.length; i++ ) {
			timer = timers[ i ];
			// Checks the timer has not already been removed
			if ( !timer() && timers[ i ] === timer ) {
				timers.splice( i--, 1 );
			}
		}

		if ( !timers.length ) {
			jQuery.fx.stop();
		}
	},

	interval: 13,

	stop: function() {
		clearInterval( timerId );
		timerId = null;
	},

	speeds: {
		slow: 600,
		fast: 200,
		// Default speed
		_default: 400
	},

	step: {
		opacity: function( fx ) {
			jQuery.style( fx.elem, "opacity", fx.now );
		},

		_default: function( fx ) {
			if ( fx.elem.style && fx.elem.style[ fx.prop ] != null ) {
				fx.elem.style[ fx.prop ] = fx.now + fx.unit;
			} else {
				fx.elem[ fx.prop ] = fx.now;
			}
		}
	}
});

// Ensure props that can't be negative don't go there on undershoot easing
jQuery.each( fxAttrs.concat.apply( [], fxAttrs ), function( i, prop ) {
	// exclude marginTop, marginLeft, marginBottom and marginRight from this list
	if ( prop.indexOf( "margin" ) ) {
		jQuery.fx.step[ prop ] = function( fx ) {
			jQuery.style( fx.elem, prop, Math.max(0, fx.now) + fx.unit );
		};
	}
});

if ( jQuery.expr && jQuery.expr.filters ) {
	jQuery.expr.filters.animated = function( elem ) {
		return jQuery.grep(jQuery.timers, function( fn ) {
			return elem === fn.elem;
		}).length;
	};
}

// Try to restore the default display value of an element
function defaultDisplay( nodeName ) {

	if ( !elemdisplay[ nodeName ] ) {

		var body = document.body,
			elem = jQuery( "<" + nodeName + ">" ).appendTo( body ),
			display = elem.css( "display" );
		elem.remove();

		// If the simple way fails,
		// get element's real default display by attaching it to a temp iframe
		if ( display === "none" || display === "" ) {
			// No iframe to use yet, so create it
			if ( !iframe ) {
				iframe = document.createElement( "iframe" );
				iframe.frameBorder = iframe.width = iframe.height = 0;
			}

			body.appendChild( iframe );

			// Create a cacheable copy of the iframe document on first call.
			// IE and Opera will allow us to reuse the iframeDoc without re-writing the fake HTML
			// document to it; WebKit & Firefox won't allow reusing the iframe document.
			if ( !iframeDoc || !iframe.createElement ) {
				iframeDoc = ( iframe.contentWindow || iframe.contentDocument ).document;
				iframeDoc.write( ( jQuery.support.boxModel ? "<!doctype html>" : "" ) + "<html><body>" );
				iframeDoc.close();
			}

			elem = iframeDoc.createElement( nodeName );

			iframeDoc.body.appendChild( elem );

			display = jQuery.css( elem, "display" );
			body.removeChild( iframe );
		}

		// Store the correct default display
		elemdisplay[ nodeName ] = display;
	}

	return elemdisplay[ nodeName ];
}




var getOffset,
	rtable = /^t(?:able|d|h)$/i,
	rroot = /^(?:body|html)$/i;

if ( "getBoundingClientRect" in document.documentElement ) {
	getOffset = function( elem, doc, docElem, box ) {
		try {
			box = elem.getBoundingClientRect();
		} catch(e) {}

		// Make sure we're not dealing with a disconnected DOM node
		if ( !box || !jQuery.contains( docElem, elem ) ) {
			return box ? { top: box.top, left: box.left } : { top: 0, left: 0 };
		}

		var body = doc.body,
			win = getWindow( doc ),
			clientTop  = docElem.clientTop  || body.clientTop  || 0,
			clientLeft = docElem.clientLeft || body.clientLeft || 0,
			scrollTop  = win.pageYOffset || jQuery.support.boxModel && docElem.scrollTop  || body.scrollTop,
			scrollLeft = win.pageXOffset || jQuery.support.boxModel && docElem.scrollLeft || body.scrollLeft,
			top  = box.top  + scrollTop  - clientTop,
			left = box.left + scrollLeft - clientLeft;

		return { top: top, left: left };
	};

} else {
	getOffset = function( elem, doc, docElem ) {
		var computedStyle,
			offsetParent = elem.offsetParent,
			prevOffsetParent = elem,
			body = doc.body,
			defaultView = doc.defaultView,
			prevComputedStyle = defaultView ? defaultView.getComputedStyle( elem, null ) : elem.currentStyle,
			top = elem.offsetTop,
			left = elem.offsetLeft;

		while ( (elem = elem.parentNode) && elem !== body && elem !== docElem ) {
			if ( jQuery.support.fixedPosition && prevComputedStyle.position === "fixed" ) {
				break;
			}

			computedStyle = defaultView ? defaultView.getComputedStyle(elem, null) : elem.currentStyle;
			top  -= elem.scrollTop;
			left -= elem.scrollLeft;

			if ( elem === offsetParent ) {
				top  += elem.offsetTop;
				left += elem.offsetLeft;

				if ( jQuery.support.doesNotAddBorder && !(jQuery.support.doesAddBorderForTableAndCells && rtable.test(elem.nodeName)) ) {
					top  += parseFloat( computedStyle.borderTopWidth  ) || 0;
					left += parseFloat( computedStyle.borderLeftWidth ) || 0;
				}

				prevOffsetParent = offsetParent;
				offsetParent = elem.offsetParent;
			}

			if ( jQuery.support.subtractsBorderForOverflowNotVisible && computedStyle.overflow !== "visible" ) {
				top  += parseFloat( computedStyle.borderTopWidth  ) || 0;
				left += parseFloat( computedStyle.borderLeftWidth ) || 0;
			}

			prevComputedStyle = computedStyle;
		}

		if ( prevComputedStyle.position === "relative" || prevComputedStyle.position === "static" ) {
			top  += body.offsetTop;
			left += body.offsetLeft;
		}

		if ( jQuery.support.fixedPosition && prevComputedStyle.position === "fixed" ) {
			top  += Math.max( docElem.scrollTop, body.scrollTop );
			left += Math.max( docElem.scrollLeft, body.scrollLeft );
		}

		return { top: top, left: left };
	};
}

jQuery.fn.offset = function( options ) {
	if ( arguments.length ) {
		return options === undefined ?
			this :
			this.each(function( i ) {
				jQuery.offset.setOffset( this, options, i );
			});
	}

	var elem = this[0],
		doc = elem && elem.ownerDocument;

	if ( !doc ) {
		return null;
	}

	if ( elem === doc.body ) {
		return jQuery.offset.bodyOffset( elem );
	}

	return getOffset( elem, doc, doc.documentElement );
};

jQuery.offset = {

	bodyOffset: function( body ) {
		var top = body.offsetTop,
			left = body.offsetLeft;

		if ( jQuery.support.doesNotIncludeMarginInBodyOffset ) {
			top  += parseFloat( jQuery.css(body, "marginTop") ) || 0;
			left += parseFloat( jQuery.css(body, "marginLeft") ) || 0;
		}

		return { top: top, left: left };
	},

	setOffset: function( elem, options, i ) {
		var position = jQuery.css( elem, "position" );

		// set position first, in-case top/left are set even on static elem
		if ( position === "static" ) {
			elem.style.position = "relative";
		}

		var curElem = jQuery( elem ),
			curOffset = curElem.offset(),
			curCSSTop = jQuery.css( elem, "top" ),
			curCSSLeft = jQuery.css( elem, "left" ),
			calculatePosition = ( position === "absolute" || position === "fixed" ) && jQuery.inArray("auto", [curCSSTop, curCSSLeft]) > -1,
			props = {}, curPosition = {}, curTop, curLeft;

		// need to be able to calculate position if either top or left is auto and position is either absolute or fixed
		if ( calculatePosition ) {
			curPosition = curElem.position();
			curTop = curPosition.top;
			curLeft = curPosition.left;
		} else {
			curTop = parseFloat( curCSSTop ) || 0;
			curLeft = parseFloat( curCSSLeft ) || 0;
		}

		if ( jQuery.isFunction( options ) ) {
			options = options.call( elem, i, curOffset );
		}

		if ( options.top != null ) {
			props.top = ( options.top - curOffset.top ) + curTop;
		}
		if ( options.left != null ) {
			props.left = ( options.left - curOffset.left ) + curLeft;
		}

		if ( "using" in options ) {
			options.using.call( elem, props );
		} else {
			curElem.css( props );
		}
	}
};


jQuery.fn.extend({

	position: function() {
		if ( !this[0] ) {
			return null;
		}

		var elem = this[0],

		// Get *real* offsetParent
		offsetParent = this.offsetParent(),

		// Get correct offsets
		offset       = this.offset(),
		parentOffset = rroot.test(offsetParent[0].nodeName) ? { top: 0, left: 0 } : offsetParent.offset();

		// Subtract element margins
		// note: when an element has margin: auto the offsetLeft and marginLeft
		// are the same in Safari causing offset.left to incorrectly be 0
		offset.top  -= parseFloat( jQuery.css(elem, "marginTop") ) || 0;
		offset.left -= parseFloat( jQuery.css(elem, "marginLeft") ) || 0;

		// Add offsetParent borders
		parentOffset.top  += parseFloat( jQuery.css(offsetParent[0], "borderTopWidth") ) || 0;
		parentOffset.left += parseFloat( jQuery.css(offsetParent[0], "borderLeftWidth") ) || 0;

		// Subtract the two offsets
		return {
			top:  offset.top  - parentOffset.top,
			left: offset.left - parentOffset.left
		};
	},

	offsetParent: function() {
		return this.map(function() {
			var offsetParent = this.offsetParent || document.body;
			while ( offsetParent && (!rroot.test(offsetParent.nodeName) && jQuery.css(offsetParent, "position") === "static") ) {
				offsetParent = offsetParent.offsetParent;
			}
			return offsetParent;
		});
	}
});


// Create scrollLeft and scrollTop methods
jQuery.each( {scrollLeft: "pageXOffset", scrollTop: "pageYOffset"}, function( method, prop ) {
	var top = /Y/.test( prop );

	jQuery.fn[ method ] = function( val ) {
		return jQuery.access( this, function( elem, method, val ) {
			var win = getWindow( elem );

			if ( val === undefined ) {
				return win ? (prop in win) ? win[ prop ] :
					jQuery.support.boxModel && win.document.documentElement[ method ] ||
						win.document.body[ method ] :
					elem[ method ];
			}

			if ( win ) {
				win.scrollTo(
					!top ? val : jQuery( win ).scrollLeft(),
					 top ? val : jQuery( win ).scrollTop()
				);

			} else {
				elem[ method ] = val;
			}
		}, method, val, arguments.length, null );
	};
});

function getWindow( elem ) {
	return jQuery.isWindow( elem ) ?
		elem :
		elem.nodeType === 9 ?
			elem.defaultView || elem.parentWindow :
			false;
}




// Create width, height, innerHeight, innerWidth, outerHeight and outerWidth methods
jQuery.each( { Height: "height", Width: "width" }, function( name, type ) {
	var clientProp = "client" + name,
		scrollProp = "scroll" + name,
		offsetProp = "offset" + name;

	// innerHeight and innerWidth
	jQuery.fn[ "inner" + name ] = function() {
		var elem = this[0];
		return elem ?
			elem.style ?
			parseFloat( jQuery.css( elem, type, "padding" ) ) :
			this[ type ]() :
			null;
	};

	// outerHeight and outerWidth
	jQuery.fn[ "outer" + name ] = function( margin ) {
		var elem = this[0];
		return elem ?
			elem.style ?
			parseFloat( jQuery.css( elem, type, margin ? "margin" : "border" ) ) :
			this[ type ]() :
			null;
	};

	jQuery.fn[ type ] = function( value ) {
		return jQuery.access( this, function( elem, type, value ) {
			var doc, docElemProp, orig, ret;

			if ( jQuery.isWindow( elem ) ) {
				// 3rd condition allows Nokia support, as it supports the docElem prop but not CSS1Compat
				doc = elem.document;
				docElemProp = doc.documentElement[ clientProp ];
				return jQuery.support.boxModel && docElemProp ||
					doc.body && doc.body[ clientProp ] || docElemProp;
			}

			// Get document width or height
			if ( elem.nodeType === 9 ) {
				// Either scroll[Width/Height] or offset[Width/Height], whichever is greater
				doc = elem.documentElement;

				// when a window > document, IE6 reports a offset[Width/Height] > client[Width/Height]
				// so we can't use max, as it'll choose the incorrect offset[Width/Height]
				// instead we use the correct client[Width/Height]
				// support:IE6
				if ( doc[ clientProp ] >= doc[ scrollProp ] ) {
					return doc[ clientProp ];
				}

				return Math.max(
					elem.body[ scrollProp ], doc[ scrollProp ],
					elem.body[ offsetProp ], doc[ offsetProp ]
				);
			}

			// Get width or height on the element
			if ( value === undefined ) {
				orig = jQuery.css( elem, type );
				ret = parseFloat( orig );
				return jQuery.isNumeric( ret ) ? ret : orig;
			}

			// Set the width or height on the element
			jQuery( elem ).css( type, value );
		}, type, value, arguments.length, null );
	};
});




// Expose jQuery to the global object
window.jQuery = window.$ = jQuery;

// Expose jQuery as an AMD module, but only for AMD loaders that
// understand the issues with loading multiple versions of jQuery
// in a page that all might call define(). The loader will indicate
// they have special allowances for multiple jQuery versions by
// specifying define.amd.jQuery = true. Register as a named module,
// since jQuery can be concatenated with other files that may use define,
// but not use a proper concatenation script that understands anonymous
// AMD modules. A named AMD is safest and most robust way to register.
// Lowercase jquery is used because AMD module names are derived from
// file names, and jQuery is normally delivered in a lowercase file name.
// Do this after creating the global so that if an AMD module wants to call
// noConflict to hide this version of jQuery, it will work.
if ( typeof define === "function" && define.amd && define.amd.jQuery ) {
	define( "jquery", [], function () { return jQuery; } );
}



})( window );
var $jq = jQuery.noConflict();/* eslint-disable */
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t(require("jquery")):"function"==typeof define&&define.amd?define(["jquery"],t):(e=e||self).mobiscroll=t(e.jQuery)}(this,function(e){"use strict";function na(e){return(na="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function u(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function s(e,t){for(var a=0;a<t.length;a++){var s=t[a];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(e,s.key,s)}}function t(e,t,a){return t&&s(e.prototype,t),a&&s(e,a),e}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&n(e,t)}function l(e){return(l=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function n(e,t){return(n=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}function c(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}function m(e,t){return!t||"object"!=typeof t&&"function"!=typeof t?c(e):t}function i(e,t,a){return(i="undefined"!=typeof Reflect&&Reflect.get?Reflect.get:function(e,t,a){var s=function(e,t){for(;!Object.prototype.hasOwnProperty.call(e,t)&&null!==(e=l(e)););return e}(e,t);if(s){var n=Object.getOwnPropertyDescriptor(s,t);return n.get?n.get.call(a):n.value}})(e,t,a||e)}e=e&&e.hasOwnProperty("default")?e.default:e;var Z=Z||{},o={},ie={},r=e.extend,d={};function h(e,a,s){var n=e;return"object"===na(a)?e.each(function(){new a.component(this,a)}):("string"==typeof a&&e.each(function(){var e,t=Z.instances[this.id];if(t&&t[a]&&void 0!==(e=t[a].apply(this,Array.prototype.slice.call(s,1))))return n=e,!1}),n)}function f(t,a,s){d[t]=function(e){return h(this,r(e,{component:a,preset:!1===s?void 0:t}),arguments)}}(Z.$=e).mobiscroll=Z,e.fn.mobiscroll=function(e){return r(this,d),h(this,e,arguments)};var p,b,v,x,g=[],T="undefined"!=typeof window,y=T&&window.matchMedia&&window.matchMedia("(prefers-color-scheme:dark)").matches,_=T?navigator.userAgent:"",w=T?navigator.platform:"",M=T?navigator.maxTouchPoints:0,C=/Safari/.test(_),k=_.match(/Android|iPhone|iPad|iPod|Windows Phone|Windows|MSIE/i),ia=T&&window.requestAnimationFrame||function(e){return setTimeout(e,20)},oa=T&&window.cancelAnimationFrame||function(e){clearTimeout(e)};function ra(){}function te(e){var t,a=[];for(t in e)a.push(e[t]);return a}function ae(e){var t,a={};if(e)for(t=0;t<e.length;t++)a[e[t]]=e[t];return a}function la(e){return 0<=e-parseFloat(e)}function de(e){return"string"==typeof e}function ue(e,t,a){return Math.max(t,Math.min(e,a))}function oe(e,t){for(e+="",t=t||2;e.length<t;)e="0"+e;return e}function ca(s,n){var i,o;return n=n||100,function(){var e=this,t=+new Date,a=arguments;i&&t<i+n?(clearTimeout(o),o=setTimeout(function(){i=t,s.apply(e,a)},n)):(i=t,s.apply(e,a))}}function ma(e){"vibrate"in navigator&&navigator.vibrate(e||50)}function X(e,t,a){return 100*(e-t)/(a-t)}function S(e,t,a){var s=a.attr(e);return void 0===s||""===s?t:"true"===s}/Android/i.test(k)?(p="android",(b=_.match(/Android\s+([\d\.]+)/i))&&(g=b[0].replace("Android ","").split("."))):/iPhone|iPad|iPod/i.test(k)||/iPhone|iPad|iPod/i.test(w)||"MacIntel"===w&&1<M?(p="ios",(b=_.match(/OS\s+([\d\_]+)/i))&&(g=b[0].replace(/_/g,".").replace("OS ","").split("."))):/Windows Phone/i.test(k)?p="wp":/Windows|MSIE/i.test(k)&&(p="windows"),v=g[0],x=g[1];var D,V=0;function da(){V++,setTimeout(function(){V--},500)}function N(e,t){if(!t.mbscClick){var a=(e.originalEvent||e).changedTouches[0],s=document.createEvent("MouseEvents");s.initMouseEvent("click",!0,!0,window,1,a.screenX,a.screenY,a.clientX,a.clientY,!1,!1,!1,!1,0,null),s.isMbscTap=!0,s.isIonicTap=!0,D=!0,t.mbscChange=!0,t.mbscClick=!0,t.dispatchEvent(s),D=!1,da(),setTimeout(function(){delete t.mbscClick})}}function ua(e,t,a){var s=e.originalEvent||e,n=(a?"page":"client")+t;return s.targetTouches&&s.targetTouches[0]?s.targetTouches[0][n]:s.changedTouches&&s.changedTouches[0]?s.changedTouches[0][n]:e[n]}function ha(e){var t=["switch","range","rating","segmented","stepper"],a=e[0],s=e.attr("data-role"),n=e.attr("type")||a.nodeName.toLowerCase();if(/(switch|range|rating|segmented|stepper|select)/.test(s))n=s;else for(var i=0;i<t.length;i++)e.is("[mbsc-"+t[i]+"]")&&(n=t[i]);return n}function fa(e,t,a){e.focus(),/(button|submit|checkbox|switch|radio)/.test(t)&&a.preventDefault(),/select/.test(t)||N(a,e)}function A(t,e,a,s,n,i){var o,r,l,c,m,d=(0,Z.$)(e);n=n||9,t.settings.tap&&d.on("touchstart.mbsc",function(e){l||(s&&e.preventDefault(),l=this,o=ua(e,"X"),r=ua(e,"Y"),c=!1,m=new Date)}).on("touchcancel.mbsc",function(){l=!1}).on("touchmove.mbsc",function(e){l&&!c&&(Math.abs(ua(e,"X")-o)>n||Math.abs(ua(e,"Y")-r)>n)&&(c=!0)}).on("touchend.mbsc",function(e){l&&(i&&new Date-m<100||!c?N(e,e.target):da(),l=!1)}),d.on("click.mbsc",function(e){s&&e.preventDefault(),a.call(this,e,t)})}function E(e){if(V&&!D&&!e.isMbscTap&&("TEXTAREA"!=e.target.nodeName||"mousedown"!=e.type))return e.stopPropagation(),e.preventDefault(),!1}function Xe(e){return e[0].innerWidth||e.innerWidth()}function F(e){var t=e.theme,a=e.themeVariant;return"auto"!=t&&t||(t=P.autoTheme),"default"==t&&(t="mobiscroll"),("dark"===a||y&&"auto"===a)&&P.themes.form[t+"-dark"]&&(t+="-dark"),t}function H(a,s,e){T&&pa(function(){pa(a).each(function(){new s(this,{})}),pa(document).on("mbsc-enhance",function(e,t){pa(e.target).is(a)?new s(e.target,t||{}):pa(a,e.target).each(function(){new s(this,t||{})})}),e&&pa(document).on("mbsc-refresh",function(e){var t;pa(e.target).is(a)?(t=ba[e.target.id])&&t.refresh():pa(a,e.target).each(function(){(t=ba[this.id])&&t.refresh()})})})}T&&(["mouseover","mousedown","mouseup","click"].forEach(function(e){document.addEventListener(e,E,!0)}),"android"==p&&v<5&&document.addEventListener("change",function(e){V&&"checkbox"==e.target.type&&!e.target.mbscChange&&(e.stopPropagation(),e.preventDefault()),delete e.target.mbscChange},!0));var P,pa=Z.$,I=+new Date,ba={},L={},O={},Y={xsmall:0,small:576,medium:768,large:992,xlarge:1200},va=pa.extend;va(o,{getCoord:ua,preventClick:da,vibrate:ma}),P=va(Z,{$:pa,version:"4.9.1",autoTheme:"mobiscroll",themes:{form:{},page:{},frame:{},scroller:{},listview:{},navigation:{},progress:{},card:{}},platform:{name:p,majorVersion:v,minorVersion:x},uid:"c5d09426",i18n:{},instances:ba,classes:L,util:o,settings:{},setDefaults:function(e){va(this.settings,e)},customTheme:function(e,t){var a,s=Z.themes,n=["frame","scroller","listview","navigation","form","page","progress","card"];for(a=0;a<n.length;a++)s[n[a]][e]=va({},s[n[a]][t],{baseTheme:t})}});function xa(o,r){var n,i,l,c,m,d,u,h,f,p=this;function b(e){var a,s=O;return m.responsive&&(a=e||Xe(n),pa.each(m.responsive,function(e,t){a>=(t.breakpoint||Y[e])&&(s=t)})),s}p.settings={},p.element=o,p._init=ra,p._destroy=ra,p._processSettings=ra,p._checkResp=function(e){if(p&&p._responsive){var t=b(e);if(c!==t)return c=t,p.init({}),!0}},p._getRespCont=function(){return pa(m.context)[0]},p.init=function(e,t){var a,s;for(a in e&&p.getVal&&(s=p.getVal()),p.settings)delete p.settings[a];m=p.settings,va(r,e),p._hasDef&&(f=P.settings),va(m,p._defaults,f,r),p._hasTheme&&(u=F(m),r.theme=u,d=P.themes[p._class]?P.themes[p._class][u]:{}),p._hasLang&&(i=P.i18n[m.lang]),va(m,d,i,f,r),n=p._getRespCont(),p._responsive&&(c=c||b(),va(m,c)),p._processSettings(c||{}),p._presets&&(l=p._presets[m.preset])&&(l=l.call(o,p,r),va(m,l,r,c)),p._init(e),e&&p.setVal&&p.setVal(void 0===t?s:t,!0),h("onInit")},p.destroy=function(){p&&(p._destroy(),h("onDestroy"),delete ba[o.id],p=null)},p.tap=function(e,t,a,s,n){A(p,e,t,a,s,n)},p.trigger=function(e,t){var a,s,n,i=[f,d,l,r];for(s=0;s<4;s++)(n=i[s])&&n[e]&&(a=n[e].call(o,t||{},p));return a},p.option=function(e,t,a){var s={},n=["data","invalid","valid","readonly"];/calendar|eventcalendar|range/.test(m.preset)&&n.push("marked","labels","colors"),"object"===na(e)?s=e:s[e]=t,n.forEach(function(e){r[e]=m[e]}),p.init(s,a)},p.getInst=function(){return p},r=r||{},h=p.trigger,p.__ready||(pa(o).addClass("mbsc-comp"),o.id?ba[o.id]&&ba[o.id].destroy():o.id="mobiscroll"+ ++I,(ba[o.id]=p).__ready=!0)}function Ze(e,t,a,s,n,i,o){var r=new Date(e,t,a,s||0,n||0,i||0,o||0);return 23==r.getHours()&&0===(s||0)&&r.setHours(r.getHours()+2),r}function re(a,e,t){if(!e)return null;function n(e){for(var t=0;o+1<a.length&&a.charAt(o+1)==e;)t++,o++;return t}function s(e,t,a){var s=""+t;if(n(e))for(;s.length<a;)s="0"+s;return s}function i(e,t,a,s){return n(e)?s[t]:a[t]}var o,r,l=va({},he,t),c="",m=!1;for(o=0;o<a.length;o++)if(m)"'"!=a.charAt(o)||n("'")?c+=a.charAt(o):m=!1;else switch(a.charAt(o)){case"d":c+=s("d",l.getDay(e),2);break;case"D":c+=i("D",e.getDay(),l.dayNamesShort,l.dayNames);break;case"o":c+=s("o",(e.getTime()-new Date(e.getFullYear(),0,0).getTime())/864e5,3);break;case"m":c+=s("m",l.getMonth(e)+1,2);break;case"M":c+=i("M",l.getMonth(e),l.monthNamesShort,l.monthNames);break;case"y":r=l.getYear(e),c+=n("y")?r:(r%100<10?"0":"")+r%100;break;case"h":var d=e.getHours();c+=s("h",12<d?d-12:0===d?12:d,2);break;case"H":c+=s("H",e.getHours(),2);break;case"i":c+=s("i",e.getMinutes(),2);break;case"s":c+=s("s",e.getSeconds(),2);break;case"a":c+=11<e.getHours()?l.pmText:l.amText;break;case"A":c+=11<e.getHours()?l.pmText.toUpperCase():l.amText.toUpperCase();break;case"'":n("'")?c+="'":m=!0;break;default:c+=a.charAt(o)}return c}function le(a,i,e){var t=va({},he,e),s=at(t.defaultValue||new Date);if(!a||!i)return s;if(i.getTime)return i;i="object"==na(i)?i.toString():i+"";function o(e){var t=c+1<a.length&&a.charAt(c+1)==e;return t&&c++,t}function n(e){o(e);var t=new RegExp("^\\d{1,"+("@"==e?14:"!"==e?20:"y"==e?4:"o"==e?3:2)+"}"),a=i.substr(T).match(t);return a?(T+=a[0].length,parseInt(a[0],10)):0}function r(e,t,a){var s,n=o(e)?a:t;for(s=0;s<n.length;s++)if(i.substr(T,n[s].length).toLowerCase()==n[s].toLowerCase())return T+=n[s].length,s+1;return 0}function l(){T++}var c,m=t.shortYearCutoff,d=t.getYear(s),u=t.getMonth(s)+1,h=t.getDay(s),f=-1,p=s.getHours(),b=s.getMinutes(),v=0,x=-1,g=!1,T=0;for(c=0;c<a.length;c++)if(g)"'"!=a.charAt(c)||o("'")?l():g=!1;else switch(a.charAt(c)){case"d":h=n("d");break;case"D":r("D",t.dayNamesShort,t.dayNames);break;case"o":f=n("o");break;case"m":u=n("m");break;case"M":u=r("M",t.monthNamesShort,t.monthNames);break;case"y":d=n("y");break;case"H":p=n("H");break;case"h":p=n("h");break;case"i":b=n("i");break;case"s":v=n("s");break;case"a":x=r("a",[t.amText,t.pmText],[t.amText,t.pmText])-1;break;case"A":x=r("A",[t.amText,t.pmText],[t.amText,t.pmText])-1;break;case"'":o("'")?l():g=!0;break;default:l()}if(d<100&&(d+=(new Date).getFullYear()-(new Date).getFullYear()%100+(d<=("string"!=typeof m?m:(new Date).getFullYear()%100+parseInt(m,10))?0:-100)),-1<f){u=1,h=f;do{var y=32-new Date(d,u-1,32,12).getDate();y<h&&(u++,h-=y)}while(y<h)}p=-1==x?p:x&&p<12?p+12:x||12!=p?p:0;var _=t.getDate(d,u-1,h,p,b,v);return t.getYear(_)!=d||t.getMonth(_)+1!=u||t.getDay(_)!=h?s:_}function Qe(e,t){return Math.round((t-e)/864e5)}function et(e){return Ze(e.getFullYear(),e.getMonth(),e.getDate())}function tt(e){return e.getFullYear()+"-"+(e.getMonth()+1)+"-"+e.getDate()}function z(e,t,a){var s,n,i={y:1,m:2,d:3,h:4,i:5,s:6,u:7,tz:8};if(a)for(s in i)(n=e[i[s]-t])&&(a[s]="tz"==s?n:1)}function ce(e,t,a){var s=window.moment||t.moment,n=t.returnFormat;if(e){if("moment"==n&&s)return s(e);if("locale"==n)return re(a,e,t);if("iso8601"==n)return function(e,t){var a="",s="";return e&&(t.h&&(s+=oe(e.getHours())+":"+oe(e.getMinutes()),t.s&&(s+=":"+oe(e.getSeconds())),t.u&&(s+="."+oe(e.getMilliseconds(),3)),t.tz&&(s+=t.tz)),t.y?(a+=e.getFullYear(),t.m&&(a+="-"+oe(e.getMonth()+1),t.d&&(a+="-"+oe(e.getDate())),t.h&&(a+="T"+s))):t.h&&(a=s)),a}(e,t.isoParts)}return e}function at(e,t,a,s){var n;return e?e.getTime?e:e.toDate?e.toDate():("string"==typeof e&&(e=e.trim()),(n=me.exec(e))?(z(n,2,s),new Date(1970,0,1,n[2]?+n[2]:0,n[3]?+n[3]:0,n[4]?+n[4]:0,n[5]?+n[5]:0)):(n=n||$.exec(e))?(z(n,0,s),new Date(n[1]?+n[1]:1970,n[2]?n[2]-1:0,n[3]?+n[3]:1,n[4]?+n[4]:0,n[5]?+n[5]:0,n[6]?+n[6]:0,n[7]?+n[7]:0)):le(t,e,a)):null}function st(e,t){return e.getFullYear()==t.getFullYear()&&e.getMonth()==t.getMonth()&&e.getDate()==t.getDate()}var $=/^(\d{4}|[+\-]\d{6})(?:-(\d{2})(?:-(\d{2}))?)?(?:T(\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{3}))?)?((Z)|([+\-])(\d{2})(?::(\d{2}))?)?)?$/,me=/^((\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{3}))?)?(?:(Z)|([+\-])(\d{2})(?::(\d{2}))?)?)?$/,nt=/^\d{1,2}(\/\d{1,2})?$/,it=/^w\d$/i,he={shortYearCutoff:"+10",monthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],dayNames:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],dayNamesShort:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],dayNamesMin:["S","M","T","W","T","F","S"],amText:"am",pmText:"pm",getYear:function(e){return e.getFullYear()},getMonth:function(e){return e.getMonth()},getDay:function(e){return e.getDate()},getDate:Ze,getMaxDayOfMonth:function(e,t){return 32-new Date(e,t,32,12).getDate()},getWeekNumber:function(e){(e=new Date(e)).setHours(0,0,0),e.setDate(e.getDate()+4-(e.getDay()||7));var t=new Date(e.getFullYear(),0,1);return Math.ceil(((e-t)/864e5+1)/7)}};function R(e){var t;for(t in e)if(void 0!==j[e[t]])return!0;return!1}function ga(e,t){if("touchstart"==e.type)pa(t).attr("data-touch","1");else if(pa(t).attr("data-touch"))return pa(t).removeAttr("data-touch"),!1;return!0}function Ta(e,t){var a,s=getComputedStyle(e[0]);return pa.each(["t","webkitT","MozT","OT","msT"],function(e,t){if(void 0!==s[t+"ransform"])return a=s[t+"ransform"],!1}),a=a.split(")")[0].split(", "),t?a[13]||a[5]:a[12]||a[4]}function ot(e){if(e){if(B[e])return B[e];var t=pa('<div style="background-color:'+e+';"></div>').appendTo("body"),a=getComputedStyle(t[0]).backgroundColor.replace(/rgb|rgba|\(|\)|\s/g,"").split(","),s=.299*a[0]+.587*a[1]+.114*a[2]<130?"#fff":"#000";return t.remove(),B[e]=s}}function Q(e,t,a,s){var n=pa(e);a?(n.scrollTop(t),s&&s()):function e(t,a,s,n,i){var o=Math.min(1,(new Date-a)/468),r=.5*(1-Math.cos(Math.PI*o)),l=s+(n-s)*r;t.scrollTop(l),l!==n?ia(function(){e(t,a,s,n,i)}):i&&i()}(n,new Date,n.scrollTop(),t,s)}o.datetime={formatDate:re,parseDate:le};var ya,j,_a,W,J,U,q,wa,B={};function rt(e,t,a,s,n,i){var o,r,l,c,m,d,u,h,f,p=s||ra;function b(e){var t;o=pa(this),h=+o.attr("data-step"),l=+o.attr("data-index"),r=!0,n&&e.stopPropagation(),"touchstart"==e.type&&o.closest(".mbsc-no-touch").removeClass("mbsc-no-touch"),"mousedown"==e.type&&e.preventDefault(),t="keydown"!=e.type?(d=ua(e,"X"),u=ua(e,"Y"),ga(e,this)):32===e.keyCode,c||!t||o.hasClass("mbsc-disabled")||(T(l,h,e)&&(o.addClass("mbsc-active"),i&&i.addRipple(o.find(".mbsc-segmented-content"),e)),"mousedown"==e.type&&pa(document).on("mousemove",v).on("mouseup",x))}function v(e){(7<Math.abs(d-ua(e,"X"))||7<Math.abs(u-ua(e,"Y")))&&(r=!0,g())}function x(e){"touchend"==e.type&&e.preventDefault(),g(),"mouseup"==e.type&&pa(document).off("mousemove",v).off("mouseup",x)}function g(){c=!1,clearInterval(f),o&&(o.removeClass("mbsc-active"),i&&setTimeout(function(){i.removeRipple()},100))}function T(e,t,a){return c||p(e)||(l=e,h=t,m=a,r=!(c=!0),setTimeout(y,100)),c}function y(){o&&o.hasClass("mbsc-disabled")?g():(!c&&r||(r=!0,t(l,h,m,y)),c&&a&&(clearInterval(f),f=setInterval(function(){t(l,h,m)},a)))}return e.on("touchstart mousedown keydown",b).on("touchmove",v).on("touchend touchcancel keyup",x),{start:T,stop:g,destroy:function(){e.off("touchstart mousedown keydown",b).off("touchmove",v).off("touchend touchcancel keyup",x)}}}T&&(j=document.createElement("modernizr").style,_a=function(){var e,t=["Webkit","Moz","O","ms"];for(e in t)if(R([t[e]+"Transform"]))return"-"+t[e].toLowerCase()+"-";return""}(),wa=_a.replace(/^\-/,"").replace(/\-$/,"").replace("moz","Moz"),ya=void 0!==j.animation?"animationend":"webkitAnimationEnd",J=void 0!==j.transition,q=(U="ios"===p&&!C)&&window.webkit&&window.webkit.messageHandlers,W=void 0===j.touchAction||U&&!q);var K="position:absolute;left:0;top:0;",G=K+"right:0;bottom:0;overflow:hidden;z-index:-1;",ee='<div style="'+G+'"><div style="'+K+'"></div></div><div style="'+G+'"><div style="'+K+'width:200%;height:200%;"></div></div>';function se(e,t,a){function s(){l.style.width="100000px",l.style.height="100000px",r.scrollLeft=1e5,r.scrollTop=1e5,d.scrollLeft=1e5,d.scrollTop=1e5}function n(){var e=new Date;c=0,u||(200<e-h&&!r.scrollTop&&!r.scrollLeft&&(h=e,s()),c=c||ia(n))}function i(){m=m||ia(o)}function o(){m=0,s(),t()}var r,l,c,m,d,u,h=0,f=document.createElement("div");return f.innerHTML=ee,f.dir="ltr",d=f.childNodes[1],r=f.childNodes[0],l=r.childNodes[0],e.appendChild(f),r.addEventListener("scroll",i),d.addEventListener("scroll",i),a?a.runOutsideAngular(function(){ia(n)}):ia(n),{detach:function(){e.removeChild(f),u=!0}}}function ne(e){e.preventDefault()}function fe(a,s,e){var C,m,d,k,u,S,D,V,N,h,t,A,f,E,F,H,p,P,I,L,O,b,r,v,Y,n,x,z,$,g,R,j,W,J=this,U=pa(a),i=[],o=new Date;function l(e){t&&t.removeClass("mbsc-active"),(t=pa(this)).hasClass("mbsc-disabled")||t.hasClass("mbsc-fr-btn-nhl")||t.addClass("mbsc-active"),"mousedown"===e.type?pa(document).on("mouseup",c):"pointerdown"===e.type&&pa(document).on("pointerup",c)}function c(e){t&&(t.removeClass("mbsc-active"),t=null),"mouseup"===e.type?pa(document).off("mouseup",c):"pointerup"===e.type&&pa(document).off("pointerup",c)}function T(e){Z.activeInstance==J&&(13!=e.keyCode||pa(e.target).is('textarea,button,input[type="button"],input[type="submit"]')&&!e.shiftKey?27==e.keyCode&&J.cancel():J.select())}function y(e){e||ge||!J._activeElm||(o=new Date,J._activeElm.focus())}function _(e){var t=pe,a=z.focusOnClose;J._markupRemove(),k.remove(),F&&(A.mbscModals--,z.scrollLock&&A.mbscLock--,A.mbscLock||d.removeClass("mbsc-fr-lock"),b&&(A.mbscIOSLock--,A.mbscIOSLock||(d.removeClass("mbsc-fr-lock-ios"),C.css({top:"",left:""}),V.scrollLeft(A.mbscScrollLeft),V.scrollTop(A.mbscScrollTop))),A.mbscModals||d.removeClass("mbsc-fr-lock-ctx"),A.mbscModals&&!x||e||(t=t||U,setTimeout(function(){void 0===a||!0===a?(be=!0,t[0].focus()):a&&pa(a)[0].focus()},200))),x=void 0,H=!1,R("onHide")}function w(){clearTimeout(n),n=setTimeout(function(){J.position(!0)&&(Y.style.visibility="hidden",Y.offsetHeight,Y.style.visibility="")},200)}function M(e){Z.activeInstance==J&&e.target.nodeType&&!v.contains(e.target)&&100<new Date-o&&(o=new Date,J._activeElm.focus())}function q(e,t){if(F)k.appendTo(C);else if(U.is("div")&&!J._hasContent)U.empty().append(k);else if(U.hasClass("mbsc-control")){var a=U.closest(".mbsc-control-w");k.insertAfter(a),a.hasClass("mbsc-select")&&a.addClass("mbsc-select-inline")}else k.insertAfter(U);var s,n,i,o;H=!0,J._markupInserted(k),R("onMarkupInserted",{target:P}),F&&z.closeOnOverlayTap&&u.on("touchstart mousedown",function(e){n||e.target!=v||(s=!(n=!0),i=ua(e,"X"),o=ua(e,"Y"))}).on("touchmove mousemove",function(e){n&&!s&&(9<Math.abs(ua(e,"X")-i)||9<Math.abs(ua(e,"Y")-o))&&(s=!0)}).on("touchcancel",function(){n=!1}).on("touchend click",function(e){n&&!s&&(J.cancel(),"touchend"==e.type&&da()),n=!1});k.on("mousedown",".mbsc-btn-e,.mbsc-fr-btn-e",ne).on("touchstart mousedown",function(e){z.stopProp&&e.stopPropagation()}).on("keydown",".mbsc-fr-btn-e",function(e){32==e.keyCode&&(e.preventDefault(),e.stopPropagation(),this.click())}).on("keydown",function(e){if(32!=e.keyCode||pa(e.target).is(_e)){if(9==e.keyCode&&F&&z.focusTrap){var t=k.find('input,select,textarea,button,[tabindex="0"]').filter(function(){return 0<this.offsetWidth||0<this.offsetHeight}),a=t.index(pa(":focus",k)),s=t.length-1,n=0;e.shiftKey&&(s=0,n=-1),a===s&&(t.eq(n)[0].focus(),e.preventDefault())}}else e.preventDefault()}).on("touchstart mousedown pointerdown",".mbsc-fr-btn-e",l).on("touchend",".mbsc-fr-btn-e",c),P.addEventListener("touchstart",function(){g||(g=!0,C.find(".mbsc-no-touch").removeClass("mbsc-no-touch"))},!0),pa.each(h,function(e,t){J.tap(pa(".mbsc-fr-btn"+e,k),function(e){t=de(t)?J.buttons[t]:t,(de(t.handler)?J.handlers[t.handler]:t.handler).call(this,e,J)},!0)}),J._attachEvents(k),!1!==J.position()&&((F||J._checkSize)&&(r=se(P,w,z.zone)),F&&(k.removeClass("mbsc-fr-pos"),f&&!e?k.addClass("mbsc-anim-in mbsc-anim-trans mbsc-anim-trans-"+f).on(ya,function e(){k.off(ya,e).removeClass("mbsc-anim-in mbsc-anim-trans mbsc-anim-trans-"+f).find(".mbsc-fr-popup").removeClass("mbsc-anim-"+f),y(t)}).find(".mbsc-fr-popup").addClass("mbsc-anim-"+f):y(t)),R("onShow",{target:P,valueText:J._tempValue}))}function B(e,t){J._isVisible||(e&&e(),!1!==J.show()&&(pe=t))}function K(){J._fillValue(),R("onSet",{valueText:J._value})}function G(){R("onCancel",{valueText:J._value})}function X(){J.setVal(null,!0)}xa.call(this,a,s,!0),J.position=function(e){var t,a,s,n,i,o,r,l,c,m,d,u,h,f,p,b,v,x,g,T={},y=0,_=0,w=0,M=0;if(!H)return!1;if(b=j,p=W,h=P.offsetHeight,(f=P.offsetWidth)&&h&&(j!==f||W!==h||!e)){if(J._checkResp(f))return!1;if(j=f,W=h,J._isFullScreen||/top|bottom/.test(z.display)?D.width(f):F&&N.width(""),J._position(k),!J._isFullScreen&&/center|bubble/.test(z.display)&&(pa(".mbsc-w-p",k).each(function(){v=this.getBoundingClientRect().width,M+=v,w=w<v?v:w}),u=f-16<M||!0===z.tabs,N.css({width:J._isLiquid?Math.min(z.maxPopupWidth,f-16):Math.ceil(u?w:M),"white-space":u?"":"nowrap"})),!1!==R("onPosition",{target:P,popup:Y,hasTabs:u,oldWidth:b,oldHeight:p,windowWidth:f,windowHeight:h})&&F)return O&&(y=V.scrollLeft(),_=V.scrollTop(),j&&S.css({width:"",height:""})),I=Y.offsetWidth,L=Y.offsetHeight,$=L<=h&&I<=f,"center"==z.display?(g=Math.max(0,y+(f-I)/2),x=Math.max(0,_+(h-L)/2)):"bubble"==z.display?(t=void 0===z.anchor?U:pa(z.anchor),r=pa(".mbsc-fr-arr-i",k)[0],i=(n=t.offset()).top+(E?_-C.offset().top:0),o=n.left+(E?y-C.offset().left:0),a=t[0].offsetWidth,s=t[0].offsetHeight,l=r.offsetWidth,c=r.offsetHeight,g=ue(o-(I-a)/2,y+3,y+f-I-3),_+h<(x=i+s+c/2)+L+8&&_<i-L-c/2?(D.removeClass("mbsc-fr-bubble-bottom").addClass("mbsc-fr-bubble-top"),x=i-L-c/2):D.removeClass("mbsc-fr-bubble-top").addClass("mbsc-fr-bubble-bottom"),pa(".mbsc-fr-arr",k).css({left:ue(o+a/2-(g+(I-l)/2),0,l)}),$=_<x&&y<g&&x+L<=_+h&&g+I<=y+f):(g=y,x="top"==z.display?_:Math.max(0,_+h-L)),O&&(m=Math.max(x+L,E?A.scrollHeight:pa(document).height()),d=Math.max(g+I,E?A.scrollWidth:pa(document).width()),S.css({width:d,height:m}),z.scroll&&"bubble"==z.display&&(_+h<x+L+8||_+h<i||i+s<_)&&V.scrollTop(Math.min(i,x+L-h+8,m-h))),T.top=Math.floor(x),T.left=Math.floor(g),D.css(T),!0}},J.attachShow=function(e,t){var a,s=pa(e).off(".mbsc"),n=s.prop("readonly");"inline"!==z.display&&((z.showOnFocus||z.showOnTap)&&s.is("input,select")&&(s.prop("readonly",!0).on("mousedown.mbsc",function(e){e.preventDefault()}).on("focus.mbsc",function(){J._isVisible&&this.blur()}),(a=pa('label[for="'+s.attr("id")+'"]')).length||(a=s.closest("label"))),s.is("select")||(z.showOnFocus&&s.on("focus.mbsc",function(){be?be=!1:B(t,s)}),z.showOnTap&&(s.on("keydown.mbsc",function(e){32!=e.keyCode&&13!=e.keyCode||(e.preventDefault(),e.stopPropagation(),B(t,s))}),J.tap(s,function(e){e.isMbscTap&&(g=!0),B(t,s)}),a&&a.length&&J.tap(a,function(e){e.preventDefault(),e.target!==s[0]&&B(t,s)}))),i.push({readOnly:n,el:s,lbl:a}))},J.select=function(){F?J.hide(!1,"set",!1,K):K()},J.cancel=function(){F?J.hide(!1,"cancel",!1,G):G()},J.clear=function(){J._clearValue(),R("onClear"),F&&J._isVisible&&!J.live?J.hide(!1,"clear",!1,X):X()},J.enable=function(){z.disabled=!1,pa.each(i,function(e,t){t.el.is("input,select")&&(t.el[0].disabled=!1)})},J.disable=function(){z.disabled=!0,pa.each(i,function(e,t){t.el.is("input,select")&&(t.el[0].disabled=!0)})},J.show=function(e,t){var a,s,n,i;if(!z.disabled&&!J._isVisible){if(J._readValue(),!1===R("onBeforeShow"))return!1;if(pe=null,f=z.animate,h=z.buttons||[],O=E||"bubble"==z.display,b=xe&&!O&&z.scrollLock,a=0<h.length,!1!==f&&("top"==z.display?f=f||"slidedown":"bottom"==z.display?f=f||"slideup":"center"!=z.display&&"bubble"!=z.display||(f=f||"pop")),F&&(W=j=0,b&&!d.hasClass("mbsc-fr-lock-ios")&&(A.mbscScrollTop=i=Math.max(0,V.scrollTop()),A.mbscScrollLeft=n=Math.max(0,V.scrollLeft()),C.css({top:-i+"px",left:-n+"px"})),d.addClass((z.scrollLock?"mbsc-fr-lock":"")+(b?" mbsc-fr-lock-ios":"")+(E?" mbsc-fr-lock-ctx":"")),pa(document.activeElement).is("input,textarea")&&document.activeElement.blur(),x=Z.activeInstance,Z.activeInstance=J,A.mbscModals=(A.mbscModals||0)+1,b&&(A.mbscIOSLock=(A.mbscIOSLock||0)+1),z.scrollLock&&(A.mbscLock=(A.mbscLock||0)+1)),s='<div lang="'+z.lang+'" class="mbsc-fr mbsc-'+z.theme+(z.baseTheme?" mbsc-"+z.baseTheme:"")+" mbsc-fr-"+z.display+" "+(z.cssClass||"")+" "+(z.compClass||"")+(J._isLiquid?" mbsc-fr-liq":"")+(F?" mbsc-fr-pos"+(z.showOverlay?"":" mbsc-fr-no-overlay"):"")+(p?" mbsc-fr-pointer":"")+(ye?" mbsc-fr-hb":"")+(g?"":" mbsc-no-touch")+(b?" mbsc-platform-ios":"")+(a?3<=h.length?" mbsc-fr-btn-block ":"":" mbsc-fr-nobtn")+'">'+(F?'<div class="mbsc-fr-persp">'+(z.showOverlay?'<div class="mbsc-fr-overlay"></div>':"")+'<div role="dialog" class="mbsc-fr-scroll">':"")+'<div class="mbsc-fr-popup'+(z.rtl?" mbsc-rtl":" mbsc-ltr")+(z.headerText?" mbsc-fr-has-hdr":"")+'">'+("bubble"===z.display?'<div class="mbsc-fr-arr-w"><div class="mbsc-fr-arr-i"><div class="mbsc-fr-arr"></div></div></div>':"")+(F?'<div class="mbsc-fr-focus" tabindex="-1"></div>':"")+'<div class="mbsc-fr-w">'+(z.headerText?'<div class="mbsc-fr-hdr">'+(de(z.headerText)?z.headerText:"")+"</div>":"")+'<div class="mbsc-fr-c">',s+=J._generateContent(),s+="</div>",a){var o,r,l,c=h.length;for(s+='<div class="mbsc-fr-btn-cont">',r=0;r<h.length;r++)l=z.btnReverse?c-r-1:r,"set"===(o=de(o=h[l])?J.buttons[o]:o).handler&&(o.parentClass="mbsc-fr-btn-s"),"cancel"===o.handler&&(o.parentClass="mbsc-fr-btn-c"),s+="<div"+(z.btnWidth?' style="width:'+100/h.length+'%"':"")+' class="mbsc-fr-btn-w '+(o.parentClass||"")+'"><div tabindex="0" role="button" class="mbsc-fr-btn'+l+" mbsc-fr-btn-e "+(void 0===o.cssClass?z.btnClass:o.cssClass)+(o.icon?" mbsc-ic mbsc-ic-"+o.icon:"")+'">'+(o.text||"")+"</div></div>";s+="</div>"}k=pa(s+="</div></div></div></div>"+(F?"</div></div>":"")),S=pa(".mbsc-fr-persp",k),u=pa(".mbsc-fr-scroll",k),N=pa(".mbsc-fr-w",k),D=pa(".mbsc-fr-popup",k),m=pa(".mbsc-fr-hdr",k),P=k[0],v=u[0],Y=D[0],J._activeElm=pa(".mbsc-fr-focus",k)[0],J._markup=k,J._isVisible=!0,J.markup=P,J._markupReady(k),R("onMarkupReady",{target:P}),F&&(pa(window).on("keydown",T),z.scrollLock&&k.on("touchmove mousewheel wheel",function(e){$&&e.preventDefault()}),z.focusTrap&&V.on("focusin",M)),F?setTimeout(function(){q(e,t)},b?100:0):q(e,t)}},J.hide=function(t,e,a,s){if(!J._isVisible||!a&&!J._isValid&&"set"==e||!a&&!1===R("onBeforeClose",{valueText:J._tempValue,button:e}))return!1;J._isVisible=!1,r&&(r.detach(),r=null),F&&(pa(document.activeElement).is("input,textarea")&&Y.contains(document.activeElement)&&document.activeElement.blur(),Z.activeInstance==J&&(Z.activeInstance=x),pa(window).off("keydown",T),V.off("focusin",M)),k&&(F&&f&&!t?k.addClass("mbsc-anim-out mbsc-anim-trans mbsc-anim-trans-"+f).on(ya,function e(){k.off(ya,e),_(t)}).find(".mbsc-fr-popup").addClass("mbsc-anim-"+f):_(t),J._detachEvents(k)),s&&s(),U.trigger("blur"),R("onClose",{valueText:J._value})},J.isVisible=function(){return J._isVisible},J.setVal=ra,J.getVal=ra,J._generateContent=ra,J._attachEvents=ra,J._detachEvents=ra,J._readValue=ra,J._clearValue=ra,J._fillValue=ra,J._markupReady=ra,J._markupInserted=ra,J._markupRemove=ra,J._position=ra,J.__processSettings=ra,J.__init=ra,J.__destroy=ra,J._destroy=function(){J.hide(!0,!1,!0),U.off(".mbsc"),pa.each(i,function(e,t){t.el.off(".mbsc").prop("readonly",t.readOnly),t.lbl&&t.lbl.off(".mbsc")}),J.__destroy()},J._updateHeader=function(){var e=z.headerText,t=e?"function"==typeof e?e.call(a,J._tempValue):e.replace(/\{value\}/i,J._tempValue):"";m.html(t||"&nbsp;")},J._getRespCont=function(){return E="body"!=z.context,V=pa(E?z.context:window),"inline"==z.display?U.is("div")?U:U.parent():V},J._processSettings=function(e){var t,a;for(J.__processSettings(e),(p=!z.touchUi)&&(z.display=e.display||s.display||"bubble",z.buttons=e.buttons||s.buttons||[],z.showOverlay=e.showOverlay||s.showOverlay||!1),z.buttons=z.buttons||("inline"!==z.display?["cancel","set"]:[]),z.headerText=void 0===z.headerText?"inline"!==z.display&&"{value}":z.headerText,h=z.buttons||[],F="inline"!==z.display,C=pa(z.context),d=E?C:pa("body,html"),A=C[0],J.live=!0,a=0;a<h.length;a++)"ok"!=(t=h[a])&&"set"!=t&&"set"!=t.handler||(J.live=!1);J.buttons.set={text:z.setText,icon:z.setIcon,handler:"set"},J.buttons.cancel={text:z.cancelText,icon:z.cancelIcon,handler:"cancel"},J.buttons.close={text:z.closeText,icon:z.closeIcon,handler:"cancel"},J.buttons.clear={text:z.clearText,icon:z.clearIcon,handler:"clear"},J._isInput=U.is("input")},J._init=function(e){var t=J._isVisible,a=t&&!k.hasClass("mbsc-fr-pos");t&&J.hide(!0,!1,!0),U.off(".mbsc"),J.__init(e),J._isLiquid="liquid"==z.layout,F?(J._readValue(),J._hasContent||z.skipShow||J.attachShow(U),t&&J.show(a)):J.show(),U.removeClass("mbsc-cloak").filter("input, select, textarea").on("change.mbsc",function(){J._preventChange||J.setVal(U.val(),!0,!1),J._preventChange=!1})},J.buttons={},J.handlers={set:J.select,cancel:J.cancel,clear:J.clear},J._value=null,J._isValid=!0,J._isVisible=!1,z=J.settings,R=J.trigger,e||J.init()}var pe,be,ve=Z.themes,xe=/(iphone|ipod)/i.test(_)&&7<=v,ge="android"==p,Te="ios"==p,ye=Te&&7<v,_e="input,select,textarea,button";fe.prototype._defaults={lang:"en",setText:"Set",selectedText:"{count} selected",closeText:"Close",cancelText:"Cancel",clearText:"Clear",context:"body",maxPopupWidth:600,disabled:!1,closeOnOverlayTap:!0,showOnFocus:ge||Te,showOnTap:!0,display:"center",scroll:!0,scrollLock:!0,showOverlay:!0,tap:!0,touchUi:!0,btnClass:"mbsc-fr-btn",btnWidth:!0,focusTrap:!0,focusOnClose:!(Te&&8==v)},L.Frame=fe,ve.frame.mobiscroll={headerText:!1,btnWidth:!1},ve.scroller.mobiscroll=va({},ve.frame.mobiscroll,{rows:5,showLabel:!1,selectedLineBorder:1,weekDays:"min",checkIcon:"ion-ios7-checkmark-empty",btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5"}),T&&pa(window).on("focus",function(){pe&&(be=!0)})
/* eslint-disable no-unused-vars */;function lt(n,e,t){var s,a,i,o,c,r,l,m,d,u,h,f,p,b,v,x,g,T,y,_,w,M,C,k,S,D,V,N,A,E,F,H,P,I,L,O,Y,z,$,R,j,W,J,U,q,B=this,K=0,G=1,X=e,Z=pa(n);function Q(e){J("onStart",{domEvent:e}),X.stopProp&&e.stopPropagation(),X.prevDef&&e.preventDefault(),X.readonly||X.lock&&w||ga(e,this)&&!_&&(s&&s.removeClass("mbsc-active"),v=!1,w||(s=pa(e.target).closest(".mbsc-btn-e",this)).length&&!s.hasClass("mbsc-disabled")&&(v=!0,o=setTimeout(function(){s.addClass("mbsc-active")},100)),M=A=!(_=!0),B.scrolled=w,Y=ua(e,"X"),z=ua(e,"Y"),f=Y,m=l=r=0,O=new Date,L=+Ta(R,U)||0,w&&me(L,we?0:1),"mousedown"===e.type&&pa(document).on("mousemove",ee).on("mouseup",ae))}function ee(e){_&&(X.stopProp&&e.stopPropagation(),f=ua(e,"X"),p=ua(e,"Y"),r=f-Y,l=p-z,m=U?l:r,v&&(Math.abs(l)>X.thresholdY||Math.abs(r)>X.thresholdX)&&(clearTimeout(o),s.removeClass("mbsc-active"),v=!1),(B.scrolled||!M&&Math.abs(m)>W)&&(A||J("onGestureStart",b),B.scrolled=A=!0,k||(k=!0,C=ia(te))),U||X.scrollLock?e.preventDefault():B.scrolled?e.preventDefault():7<Math.abs(l)&&(M=!0,B.scrolled=!0,ae()))}function te(){T&&(m=ue(m,-P*T,P*T)),me(ue(L+m,y-h,g+h)),k=!1}function ae(e){if(_){var t,a=new Date-O;X.stopProp&&e&&e.stopPropagation(),oa(C),k=!1,!M&&B.scrolled&&(X.momentum&&a<300&&(t=m/a,m=Math.max(Math.abs(m),t*t/X.speedUnit)*(m<0?-1:1)),ce(m)),v&&(clearTimeout(o),s.addClass("mbsc-active"),setTimeout(function(){s.removeClass("mbsc-active")},100),M||B.scrolled||J("onBtnTap",{target:s[0],domEvent:e})),e&&"mouseup"==e.type&&pa(document).off("mousemove",ee).off("mouseup",ae),_=!1}}function se(e){if(e=e.originalEvent||e,m=U?null==e.deltaY?e.wheelDelta||e.detail:e.deltaY:e.deltaX,J("onStart",{domEvent:e}),X.stopProp&&e.stopPropagation(),m){if(e.preventDefault(),e.deltaMode&&1==e.deltaMode&&(m*=15),m=ue(-m,-F,F),L=q,X.readonly)return;if(A||le(),L+m<y&&(L=y,m=0),g<L+m&&(L=g,m=0),k||(k=!0,C=ia(te)),!m&&A)return;A=!0,clearTimeout(E),E=setTimeout(function(){oa(C),A=k=!1,ce(m)},200)}}function ne(e){J("onStart",{domEvent:e}),X.readonly||(e.stopPropagation(),L=q,A=!1,e.target==S?(z=ua(e,"Y",!0),pa(document).on("mousemove",ie).on("mouseup",oe)):(z=a.offset().top,ie(e),oe()))}function ie(e){var t=(ua(e,"Y",!0)-z)/c;m=x?ue(m=-(T*P*2+c)*t,-P*T,P*T):(y-g-c)*t,A||le(),A=!0,me(ue(L+m,y-h,g+h))}function oe(){L=q,ce(0),pa(document).off("mousemove",ie).off("mouseup",oe)}function re(e){e.stopPropagation()}function le(){J("onGestureStart",b={posX:U?0:q,posY:U?q:0,originX:U?0:L,originY:U?L:0,direction:0<m?U?270:360:U?90:180})}function ce(e){var t,a,s;if(T&&(e=ue(e,-P*T,P*T)),s=ue(Math.round((L+e)/P)*P,y,g),I){if(e<0){for(t=I.length-1;0<=t;t--)if(Math.abs(s)+c>=I[t].breakpoint){G=2,s=I[K=t].snap2;break}}else if(0<=e)for(t=0;t<I.length;t++)if(Math.abs(s)<=I[t].breakpoint){G=1,s=I[K=t].snap1;break}s=ue(s,y,g)}a=X.time||(q<y||g<q?1e3:Math.max(1e3,Math.abs(s-q)*X.timeUnit)),b.destinationX=U?0:s,b.destinationY=U?s:0,b.duration=a,b.transitionTiming=u,J("onGestureEnd",b),B.scroll(s,a)}function me(t,e,a,s){function n(){clearInterval(H),clearTimeout(j),w=!1,q=t,b.posX=U?0:t,b.posY=U?t:0,o&&J("onMove",b),r&&J("onAnimationEnd",b),s&&s()}var i,o=t!=q,r=1<e,l=e?_a+"transform "+Math.round(e)+"ms "+u:"";b={posX:U?0:q,posY:U?q:0,originX:U?0:L,originY:U?L:0,direction:0<t-q?U?270:360:U?90:180},q=t,r&&(b.destinationX=U?0:t,b.destinationY=U?t:0,b.duration=e,b.transitionTiming=u,J("onAnimationStart",b)),$[wa+"Transition"]=l,$[wa+"Transform"]="translate3d("+(U?"0,"+t+"px,":t+"px,0,")+"0)",S&&D&&(i=x?(V-t)/(T*P*2):(t-g)/(y-g),S.style[wa+"Transition"]=l,S.style[wa+"Transform"]="translate3d(0,"+Math.max(0,Math.min((c-D)*i,c-D))+"px,0)"),!o&&!w||!e||e<=1?n():e&&(w=!a,clearInterval(H),H=setInterval(function(){var e=+Ta(R,U)||0;b.posX=U?0:e,b.posY=U?e:0,J("onMove",b),Math.abs(e-t)<2&&n()},100),clearTimeout(j),j=setTimeout(function(){n()},e)),X.sync&&X.sync(t,e,u)}xa.call(this,n,e,!0),B.scrolled=!1,B.scroll=function(e,t,a,s){e=ue(e=la(e)?Math.round(e/P)*P:Math.ceil((pa(e,n).length?Math.round(R.offset()[d]-pa(e,n).offset()[d]):q)/P)*P,y,g),K=Math.round(e/P),L=q,V=T*P+e,me(e,t,a,s)},B.refresh=function(e){var t;for(c=(void 0===X.contSize?U?Z.height():Z.width():X.contSize)||0,g=(void 0===X.maxScroll?0:X.maxScroll)||0,y=Math.min(g,void 0===X.minScroll?Math.min(0,U?c-R.height():c-R.width()):X.minScroll)||0,I=null,!U&&X.rtl&&(t=g,g=-y,y=-t),de(X.snap)&&(I=[],R.find(X.snap).each(function(){var e=U?this.offsetTop:this.offsetLeft,t=U?this.offsetHeight:this.offsetWidth;I.push({breakpoint:e+t/2,snap1:-e,snap2:c-e-t})})),P=la(X.snap)?X.snap:1,T=X.snap?X.maxSnapScroll:0,u=X.easing,h=X.elastic?la(X.snap)?P:la(X.elastic)?X.elastic:0:0,F=P;44<F;)F/=2;F=Math.round(44/F)*F,S&&(x=y==-1/0||g==1/0,D=y<g?Math.max(20,c*c/(g-y+c)):0,S.style.height=D+"px",N.style.height=D?"":0),void 0===q&&(q=X.initialPos,K=Math.round(q/P)),e||B.scroll(X.snap?I?I[K]["snap"+G]:K*P:q)},B._processSettings=function(){U="Y"==X.axis,d=U?"top":"left",R=X.moveElement||Z.children().eq(0),$=R[0].style,W=U?X.thresholdY:X.thresholdX,X.scrollbar&&(i=X.scrollbar,a=i.find(".mbsc-sc-bar"),S=a[0],N=i[0])},B._init=function(){B.refresh(),Z.on("touchstart mousedown",Q).on("touchmove",ee).on("touchend touchcancel",ae),X.mousewheel&&Z.on("wheel mousewheel",se),S&&i.on("mousedown",ne).on("click",re),n.addEventListener("click",function(e){B.scrolled&&(B.scrolled=!1,e.stopPropagation(),e.preventDefault())},!0)},B._destroy=function(){clearInterval(H),Z.off("touchstart mousedown",Q).off("touchmove",ee).off("touchend touchcancel",ae).off("wheel mousewheel",se),S&&i.off("mousedown",ne).off("click",re)},X=B.settings,J=B.trigger,t||B.init()}var we="ios"==p;lt.prototype={_defaults:{speedUnit:.0022,timeUnit:3,initialPos:0,axis:"Y",thresholdX:10,thresholdY:5,easing:"cubic-bezier(0.190, 1.000, 0.220, 1.000)",stopProp:!0,momentum:!0,mousewheel:!0,elastic:!0}};
/* eslint-disable no-unused-vars */
var Me={},Ce=T?window.CSS:null,ke=Ce&&Ce.supports&&Ce.supports("(transform-style: preserve-3d)");function Se(e){return(e+"").replace('"',"___")}function De(h,t,e){var a,c,x,f,g,l,n,T,y,m,d,p,_,b,w,v,i,M=40,C=1e3,k=this,o=pa(h);function s(e){var t,a,s=+pa(this).attr("data-index");38==e.keyCode?(t=!0,a=-1):40==e.keyCode?(t=!0,a=1):32==e.keyCode&&(t=!0,u(s,pa(e.target))),t&&(e.stopPropagation(),e.preventDefault(),a&&n.start(s,a,e))}function r(){n.stop()}function u(e,t){var a=v[e],s=+t.attr("data-index"),n=E(a,s),i=k._tempSelected[e],o=la(a.multiple)?a.multiple:1/0;!1!==b("onItemTap",{target:t[0],index:e,value:n,selected:t.hasClass("mbsc-sc-itm-sel")})&&(a.multiple&&!a._disabled[n]&&(void 0!==i[n]?(t.removeClass(g).removeAttr("aria-selected"),delete i[n]):(1==o&&(k._tempSelected[e]=i={},a._$markup.find(".mbsc-sc-itm-sel").removeClass(g).removeAttr("aria-selected")),te(i).length<o&&(t.addClass(g).attr("aria-selected","true"),i[n]=n))),Y(a,e,s,C,a._index<s?1:2,!0,a.multiple),k.live&&(!a.multiple||1===a.multiple&&_.tapSelect)&&(!0===_.setOnTap||_.setOnTap[e])&&setTimeout(function(){k.select()},_.tapSelect?0:200))}function S(e){return-(e.max-e._offset-(e.multiple&&!f?Math.floor(_.rows/2):0))*y}function D(e){return-(e.min-e._offset+(e.multiple&&!f?Math.floor(_.rows/2):0))*y}function V(e,t){return(e._array?e._map[t]:+e.getIndex(t,k))||0}function N(e,t){var a=e.data;if(t>=e.min&&t<=e.max)return e._array?e.circular?pa(a).get(t%e._length):a[t]:pa.isFunction(a)?a(t,k):""}function A(e){return pa.isPlainObject(e)?void 0!==e.value?e.value:e.display:e}function E(e,t){return A(N(e,t))}function F(e,t,a){var s=v[e];Y(s,e,s._index+t,_.delay+100,1==t?1:2,!1,!1,"keydown"==a.type)}function H(e){return pa.isArray(_.readonly)?_.readonly[e]:_.readonly}function P(a,e,t){var s=a._index-a._batch;return a.data=a.data||[],a.key=void 0!==a.key?a.key:e,a.label=void 0!==a.label?a.label:e,a._map={},a._array=pa.isArray(a.data),a._array&&(a._length=a.data.length,pa.each(a.data,function(e,t){a._map[A(t)]=e})),a.circular=void 0===_.circular?void 0===a.circular?a._array&&a._length>_.rows:a.circular:pa.isArray(_.circular)?_.circular[e]:_.circular,a.min=a._array?a.circular?-1/0:0:void 0===a.min?-1/0:a.min,a.max=a._array?a.circular?1/0:a._length-1:void 0===a.max?1/0:a.max,a._nr=e,a._index=V(a,T[e]),a._disabled={},a._batch=0,a._current=a._index,a._first=a._index-M,a._last=a._index+M,a._offset=a._first,t?(a._offset-=a._margin/y+(a._index-s),a._margin+=(a._index-s)*y):a._margin=0,a._refresh=function(e){va(a._scroller.settings,{minScroll:S(a),maxScroll:D(a)}),a._scroller.refresh(e)},i[a.key]=a}function I(e,t,a,s,n){var i,o,r,l,c,m,d,u,h,f,p="",b=k._tempSelected[t],v=e._disabled||{};for(i=a;i<=s;i++)r=N(e,i),h=r,c=void 0===(f=pa.isPlainObject(h)?h.display:h)?"":f,l=A(r),o=r&&void 0!==r.cssClass?r.cssClass:"",m=r&&void 0!==r.label?r.label:"",d=r&&r.invalid,u=void 0!==l&&l==T[t]&&!e.multiple,p+='<div role="option" tabindex="-1" aria-selected="'+!!b[l]+'" class="mbsc-sc-itm '+(n?"mbsc-sc-itm-3d ":"")+o+" "+(u?"mbsc-sc-itm-sel ":"")+(b[l]?g:"")+(void 0===l?" mbsc-sc-itm-ph":" mbsc-btn-e")+(d?" mbsc-sc-itm-inv-h mbsc-disabled":"")+(v[l]?" mbsc-sc-itm-inv mbsc-disabled":"")+'" data-index="'+i+'" data-val="'+Se(l)+'"'+(m?' aria-label="'+m+'"':"")+(u?' aria-selected="true"':"")+' style="height:'+y+"px;line-height:"+y+"px;"+(n?_a+"transform:rotateX("+(e._offset-i)*x%360+"deg) translateZ("+y*_.rows/2+"px);":"")+'">'+(1<w?'<div class="mbsc-sc-itm-ml" style="line-height:'+Math.round(y/w)+"px;font-size:"+Math.round(y/w*.8)+'px;">':"")+c+(1<w?"</div>":"")+"</div>";return p}function L(e,t,a,s){var n,i=v[e],o=s||i._disabled,r=V(i,t),l=E(i,r),c=l,m=l,d=0,u=0;if(!0===o[l]){for(n=0;r-d>=i.min&&o[c]&&n<100;)n++,c=E(i,r-++d);for(n=0;r+u<i.max&&o[m]&&n<100;)n++,m=E(i,r+ ++u);l=(u<d&&u&&2!==a||!d||r-d<0||1==a)&&!o[m]?m:c}return l}function O(s,n,i,e,o,t,r){var l,c,m,d,u=k._isVisible;p=!0,d=_.validate.call(h,{values:T.slice(0),index:n,direction:i},k)||{},p=!1,d.valid&&(k._tempWheelArray=T=d.valid.slice(0)),t||pa.each(v,function(e,a){if(u&&a._$markup.find(".mbsc-sc-itm-inv").removeClass("mbsc-sc-itm-inv mbsc-disabled"),a._disabled={},d.disabled&&d.disabled[e]&&pa.each(d.disabled[e],function(e,t){a._disabled[t]=!0,u&&a._$markup.find('.mbsc-sc-itm[data-val="'+Se(t)+'"]').addClass("mbsc-sc-itm-inv mbsc-disabled")}),T[e]=a.multiple?T[e]:L(e,T[e],i),u){if(a.multiple&&void 0!==n||a._$markup.find(".mbsc-sc-itm-sel").removeClass(g).removeAttr("aria-selected"),c=V(a,T[e]),l=c-a._index+a._batch,Math.abs(l)>2*M+1&&(m=l+(2*M+1)*(0<l?-1:1),a._offset+=m,a._margin-=m*y,a._refresh()),a._index=c+a._batch,a.multiple){if(void 0===n)for(var t in k._tempSelected[e])a._$markup.find('.mbsc-sc-itm[data-val="'+Se(t)+'"]').addClass(g).attr("aria-selected","true")}else a._$markup.find('.mbsc-sc-itm[data-val="'+Se(T[e])+'"]').addClass("mbsc-sc-itm-sel").attr("aria-selected","true");a._$active&&a._$active.attr("tabindex",-1),a._$active=a._$markup.find('.mbsc-sc-itm[data-index="'+a._index+'"]').eq(f&&a.multiple?1:0).attr("tabindex",0),r&&n===e&&a._$active.length&&(a._$active[0].focus(),a._$scroller.parent().scrollTop(0)),a._scroller.scroll(-(c-a._offset+a._batch)*y,n===e||void 0===n?s:C,o)}}),b("onValidated",{index:n,time:s}),k._tempValue=_.formatValue.call(h,T,k),u&&k._updateHeader(),k.live&&function(e,t){var a=v[e];return a&&(!a.multiple||1!==a.multiple&&t&&(!0===_.setOnTap||_.setOnTap[e]))}(n,t)&&(k._hasValue=e||k._hasValue,z(e,e,0,!0),e&&b("onSet",{valueText:k._value})),e&&b("onChange",{index:n,valueText:k._tempValue})}function Y(e,t,a,s,n,i,o,r){var l=E(e,a);void 0!==l&&(T[t]=l,e._batch=e._array?Math.floor(a/e._length)*e._length:0,e._index=a,setTimeout(function(){O(s,t,n,!0,i,o,r)},10))}function z(e,t,a,s,n){if(s?k._tempValue=_.formatValue.call(h,k._tempWheelArray,k):O(a),!n){k._wheelArray=[];for(var i=0;i<T.length;i++)k._wheelArray[i]=v[i]&&v[i].multiple?Object.keys(k._tempSelected[i]||{})[0]:T[i];k._value=k._hasValue?k._tempValue:null,k._selected=va(!0,{},k._tempSelected)}e&&(k._isInput&&o.val(k._hasValue?k._tempValue:""),b("onFill",{valueText:k._hasValue?k._tempValue:"",change:t}),t&&(k._preventChange=!0,o.trigger("change")))}fe.call(this,h,t,!0),k.setVal=k._setVal=function(e,t,a,s,n){k._hasValue=null!=e,k._tempWheelArray=T=pa.isArray(e)?e.slice(0):_.parseValue.call(h,e,k)||[],z(t,void 0===a?t:a,n,!1,s)},k.getVal=k._getVal=function(e){var t=k._hasValue||e?k[e?"_tempValue":"_value"]:null;return la(t)?+t:t},k.setArrayVal=k.setVal,k.getArrayVal=function(e){return e?k._tempWheelArray:k._wheelArray},k.changeWheel=function(e,t,a){var s,n;pa.each(e,function(e,t){(n=i[e])&&(s=n._nr,va(n,t),P(n,s,!0),k._isVisible&&(f&&n._$3d.html(I(n,s,n._first+M-c+1,n._last-M+c,!0)),n._$scroller.html(I(n,s,n._first,n._last)).css("margin-top",n._margin+"px"),n._refresh(p)))}),!k._isVisible||k._isLiquid||p||k.position(),p||O(t,void 0,void 0,a)},k.getValidValue=L,k._generateContent=function(){var a,s=0,n="",i=f?_a+"transform: translateZ("+(y*_.rows/2+3)+"px);":"",o='<div class="mbsc-sc-whl-l" style="'+i+"height:"+y+"px;margin-top:-"+(y/2+(_.selectedLineBorder||0))+'px;"></div>',r=0;return pa.each(_.wheels,function(e,t){n+='<div class="mbsc-w-p mbsc-sc-whl-gr-c'+(f?" mbsc-sc-whl-gr-3d-c":"")+(_.showLabel?" mbsc-sc-lbl-v":"")+'">'+o+'<div class="mbsc-sc-whl-gr'+(f?" mbsc-sc-whl-gr-3d":"")+(l?" mbsc-sc-cp":"")+(_.width||_.maxWidth?'"':'" style="max-width:'+_.maxPopupWidth+'px;"')+">",pa.each(t,function(e,t){k._tempSelected[r]=va({},k._selected[r]),v[r]=P(t,r),s+=_.maxWidth?_.maxWidth[r]||_.maxWidth:_.width?_.width[r]||_.width:0,a=void 0!==t.label?t.label:e,n+='<div class="mbsc-sc-whl-w '+(t.cssClass||"")+(t.multiple?" mbsc-sc-whl-multi":"")+'" style="'+(_.width?"width:"+(_.width[r]||_.width)+"px;":(_.minWidth?"min-width:"+(_.minWidth[r]||_.minWidth)+"px;":"")+(_.maxWidth?"max-width:"+(_.maxWidth[r]||_.maxWidth)+"px;":""))+'">'+(d?'<div class="mbsc-sc-bar-c"><div class="mbsc-sc-bar"></div></div>':"")+'<div class="mbsc-sc-whl-o" style="'+i+'"></div>'+o+'<div aria-live="off" aria-label="'+a+'"'+(t.multiple?' aria-multiselectable="true"':"")+' role="listbox" data-index="'+r+'" class="mbsc-sc-whl" style="height:'+_.rows*y*(f?1.1:1)+'px;">'+(l?'<div data-index="'+r+'" data-step="1" class="mbsc-sc-btn mbsc-sc-btn-plus '+(_.btnPlusClass||"")+'"></div><div data-index="'+r+'" data-step="-1" class="mbsc-sc-btn mbsc-sc-btn-minus '+(_.btnMinusClass||"")+'"></div>':"")+'<div class="mbsc-sc-lbl">'+a+'</div><div class="mbsc-sc-whl-c" style="height:'+m+"px;margin-top:-"+(m/2+1)+"px;"+i+'"><div class="mbsc-sc-whl-sc" style="top:'+(m-y)/2+'px;">',n+=I(t,r,t._first,t._last)+"</div></div>",f&&(n+='<div class="mbsc-sc-whl-3d" style="height:'+y+"px;margin-top:-"+y/2+'px;">',n+=I(t,r,t._first+M-c+1,t._last-M+c,!0),n+="</div>"),n+="</div></div>",r++}),n+="</div></div>"}),s&&(_.maxPopupWidth=s),n},k._attachEvents=function(e){n=rt(pa(".mbsc-sc-btn",e),F,_.delay,H,!0),pa(".mbsc-sc-whl",e).on("keydown",s).on("keyup",r)},k._detachEvents=function(){n.stop();for(var e=0;e<v.length;e++)v[e]._scroller.destroy()},k._markupReady=function(e){pa(".mbsc-sc-whl-w",a=e).each(function(n){var i,e=pa(this),o=v[n];o._$markup=e,o._$scroller=pa(".mbsc-sc-whl-sc",this),o._$3d=pa(".mbsc-sc-whl-3d",this),o._scroller=new lt(this,{mousewheel:_.mousewheel,moveElement:o._$scroller,scrollbar:pa(".mbsc-sc-bar-c",this),initialPos:(o._first-o._index)*y,contSize:_.rows*y,snap:y,minScroll:S(o),maxScroll:D(o),maxSnapScroll:M,prevDef:!0,stopProp:!0,timeUnit:3,easing:"cubic-bezier(0.190, 1.000, 0.220, 1.000)",sync:function(e,t,a){var s=t?_a+"transform "+Math.round(t)+"ms "+a:"";f&&(o._$3d[0].style[wa+"Transition"]=s,o._$3d[0].style[wa+"Transform"]="rotateX("+-e/y*x+"deg)")},onStart:function(e,t){t.settings.readonly=H(n)},onGestureStart:function(){e.addClass("mbsc-sc-whl-a mbsc-sc-whl-anim"),b("onWheelGestureStart",{index:n})},onGestureEnd:function(e){var t=90==e.direction?1:2,a=e.duration,s=e.destinationY;i=Math.round(-s/y)+o._offset,Y(o,n,i,a,t)},onAnimationStart:function(){e.addClass("mbsc-sc-whl-anim")},onAnimationEnd:function(){e.removeClass("mbsc-sc-whl-a mbsc-sc-whl-anim"),b("onWheelAnimationEnd",{index:n}),o._$3d.find(".mbsc-sc-itm-del").remove()},onMove:function(e){!function(e,t,a){var s=Math.round(-a/y)+e._offset,n=s-e._current,i=e._first,o=e._last,r=i+M-c+1,l=o-M+c;n&&(e._first+=n,e._last+=n,e._current=s,0<n?(e._$scroller.append(I(e,t,Math.max(o+1,i+n),o+n)),pa(".mbsc-sc-itm",e._$scroller).slice(0,Math.min(n,o-i+1)).remove(),f&&(e._$3d.append(I(e,t,Math.max(l+1,r+n),l+n,!0)),pa(".mbsc-sc-itm",e._$3d).slice(0,Math.min(n,l-r+1)).attr("class","mbsc-sc-itm-del"))):n<0&&(e._$scroller.prepend(I(e,t,i+n,Math.min(i-1,o+n))),pa(".mbsc-sc-itm",e._$scroller).slice(Math.max(n,i-o-1)).remove(),f&&(e._$3d.prepend(I(e,t,r+n,Math.min(r-1,l+n),!0)),pa(".mbsc-sc-itm",e._$3d).slice(Math.max(n,r-l-1)).attr("class","mbsc-sc-itm-del"))),e._margin+=n*y,e._$scroller.css("margin-top",e._margin+"px"))}(o,n,e.posY)},onBtnTap:function(e){u(n,pa(e.target))}})}),O()},k._fillValue=function(){z(k._hasValue=!0,!0,0,!0)},k._clearValue=function(){pa(".mbsc-sc-whl-multi .mbsc-sc-itm-sel",a).removeClass(g).removeAttr("aria-selected")},k._readValue=function(){var e=o.val()||"",a=0;""!==e&&(k._hasValue=!0),k._tempWheelArray=T=k._hasValue&&k._wheelArray?k._wheelArray.slice(0):_.parseValue.call(h,e,k)||[],k._tempSelected=va(!0,{},k._selected),pa.each(_.wheels,function(e,t){pa.each(t,function(e,t){v[a]=P(t,a),a++})}),z(!1,!1,0,!0),b("onRead")},k.__processSettings=function(e){_=k.settings,b=k.trigger,w=_.multiline,g="mbsc-sc-itm-sel mbsc-ic mbsc-ic-"+_.checkIcon,(d=!_.touchUi)&&(_.tapSelect=!0,_.circular=!1,_.rows=e.rows||t.rows||7)},k.__init=function(e){e&&(k._wheelArray=null),v=[],i={},l=_.showScrollArrows,f=_.scroll3d&&ke&&!l&&!d&&("ios"==_.theme||"ios"==_.baseTheme),y=_.height,m=f?2*Math.round((y-.03*(y*_.rows/2+3))/2):y,c=Math.round(1.8*_.rows),x=360/(2*c),l&&(_.rows=Math.max(3,_.rows))},k._getItemValue=A,k._tempSelected={},k._selected={},e||k.init()}De.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_class:"scroller",_presets:Me,_defaults:va({},fe.prototype._defaults,{minWidth:80,height:40,rows:3,multiline:1,delay:200,readonly:!1,showLabel:!0,setOnTap:!1,wheels:[],preset:"",speedUnit:.0012,timeUnit:.08,checkIcon:"checkmark",compClass:"mbsc-sc",validate:function(){},formatValue:function(e){return e.join(" ")},parseValue:function(e,a){var s,n,i=[],o=[],r=0;return null!=e&&(i=(e+"").split(" ")),pa.each(a.settings.wheels,function(e,t){pa.each(t,function(e,t){n=t.data,s=a._getItemValue(n[0]),pa.each(n,function(e,t){if(i[r]==a._getItemValue(t))return s=a._getItemValue(t),!1}),o.push(s),r++})}),o}})},L.Scroller=De;function ct(x){function e(e){var t,a,s,n,i=[];if(e){for(t=0;t<e.length;t++)if((a=e[t]).start&&a.end&&!me.test(a.start))for(s=new Date(at(a.start,S,H)),n=new Date(at(a.end,S,H));s<=n;)i.push(Ze(s.getFullYear(),s.getMonth(),s.getDate())),s.setDate(s.getDate()+1);else i.push(a);return i}return e}function A(e,t,a,s){return Math.min(s,Math.floor(e/t)*t+a)}function t(e,t,a){return Math.floor((a-t)/e)*e+t}function i(e){return e.getFullYear()+"-"+oe(e.getMonth()+1)+"-"+oe(e.getDate())}function r(e,t,a,s){var n;return void 0===C[t]||(n=+e[C[t]],isNaN(n))?a?ne[t](a):void 0!==o[t]?o[t]:ne[t](s):n}function g(e){var t,a=new Date((new Date).setHours(0,0,0,0));if(null===e)return e;void 0!==C.dd&&(t=e[C.dd].split("-"),t=new Date(t[0],t[1]-1,t[2])),void 0!==C.tt&&(t=t||a,t=new Date(t.getTime()+e[C.tt]%86400*1e3));var s=r(e,"y",t,a),n=r(e,"m",t,a),i=Math.min(r(e,"d",t,a),H.getMaxDayOfMonth(s,n)),o=r(e,"h",t,a);return H.getDate(s,n,i,R&&r(e,"a",t,a)?o+12:o,r(e,"i",t,a),r(e,"s",t,a),r(e,"u",t,a))}function T(e,t){var a,s,n=["y","m","d","a","h","i","s","u","dd","tt"],i=[];if(null==e)return e;for(a=0;a<n.length;a++)void 0!==C[s=n[a]]&&(i[C[s]]=ne[s](e)),t&&(o[s]=ne[s](e));return i}function a(e,t){return t?Math.floor(new Date(e)/864e5):e.getMonth()+12*(e.getFullYear()-1970)}function u(e){return{value:e,display:(/yy/i.test(N)?e:(e+"").substr(2,2))+(H.yearSuffix||"")}}function h(e){return e}function f(s){var n=/d/i.test(s);return{label:"",cssClass:"mbsc-dt-whl-date",min:Y?a(i(Y),n):void 0,max:z?a(i(z),n):void 0,data:function(e){var t=new Date((new Date).setHours(0,0,0,0)),a=n?new Date(864e5*e):new Date(1970,e,1);return n&&(a=new Date(a.getUTCFullYear(),a.getUTCMonth(),a.getUTCDate())),{invalid:n&&!_(a,!0),value:i(a),display:t.getTime()==a.getTime()?H.todayText:re(s,a,H)}},getIndex:function(e){return a(e,n)}}}function p(e){var t,a,s,n=[];for(/s/i.test(e)?a=q:/i/i.test(e)?a=60*U:/h/i.test(e)&&(a=3600*J),E=se.tt=a,t=0;t<86400;t+=a)s=new Date((new Date).setHours(0,0,0,0)+1e3*t),n.push({value:t,display:re(e,s,H)});return{label:"",cssClass:"mbsc-dt-whl-time",data:n}}function y(e,t){return H.getYear(e)===H.getYear(t)&&H.getMonth(e)===H.getMonth(t)}function _(e,t){return!(!t&&e<Y)&&(!(!t&&z<e)&&(!!s(e,O)||!s(e,L)))}function s(e,t){var a,s,n;if(t)for(s=0;s<t.length;s++)if(n=(a=t[s])+"",!a.start)if(it.test(n)){if((n=+n.replace("w",""))==e.getDay())return!0}else if(nt.test(n)){if((n=n.split("/"))[1]){if(n[0]-1==e.getMonth()&&n[1]==e.getDate())return!0}else if(n[0]==e.getDate())return!0}else if(a=at(a,S,H),e.getFullYear()==a.getFullYear()&&e.getMonth()==a.getMonth()&&e.getDate()==a.getDate())return!0;return!1}function w(e,t,a,s,n,i,o){var r,l,c,m;if(e)for(l=0;l<e.length;l++)if(m=(r=e[l])+"",!r.start)if(it.test(m))for(c=(m=+m.replace("w",""))-s;c<n;c+=7)0<=c&&(i[c+1]=o);else nt.test(m)?(m=m.split("/"))[1]?m[0]-1==a&&(i[m[1]]=o):i[m[0]]=o:(r=at(r,S,H),H.getYear(r)==t&&H.getMonth(r)==a&&(i[H.getDay(r)]=o))}function M(e,t,a,s,n,i,o,r){var l,c,m,d,u,h,f,p,b,v,x,g,T,y,_,w,M,C,k,S,D={},V=H.getDate(s,n,i),N=["a","h","i","s"];if(e){for(f=0;f<e.length;f++)(x=e[f]).start&&(x.apply=!1,C=(M=(m=x.d)+"").split("/"),m&&(m.getTime&&s==H.getYear(m)&&n==H.getMonth(m)&&i==H.getDay(m)||!it.test(M)&&(C[1]&&i==C[1]&&n==C[0]-1||!C[1]&&i==C[0])||it.test(M)&&V.getDay()==+M.replace("w",""))&&(x.apply=!0,D[V]=!0));for(f=0;f<e.length;f++)if(x=e[f],w=l=0,p=te[a],b=ae[a],c=!(_=y=!0),x.start&&(x.apply||!x.d&&!D[V])){for(g=x.start.split(":"),T=x.end.split(":"),v=0;v<3;v++)void 0===g[v]&&(g[v]=0),void 0===T[v]&&(T[v]=59),g[v]=+g[v],T[v]=+T[v];if("tt"==a)p=A(Math.round((new Date(V).setHours(g[0],g[1],g[2])-new Date(V).setHours(0,0,0,0))/1e3),E,0,86400),b=A(Math.round((new Date(V).setHours(T[0],T[1],T[2])-new Date(V).setHours(0,0,0,0))/1e3),E,0,86400);else{for(g.unshift(11<g[0]?1:0),T.unshift(11<T[0]?1:0),R&&(12<=g[1]&&(g[1]=g[1]-12),12<=T[1]&&(T[1]=T[1]-12)),v=0;v<t;v++)void 0!==F[v]&&(k=A(g[v],se[N[v]],te[N[v]],ae[N[v]]),S=A(T[v],se[N[v]],te[N[v]],ae[N[v]]),h=u=d=0,R&&1==v&&(d=g[0]?12:0,u=T[0]?12:0,h=F[0]?12:0),y||(k=0),_||(S=ae[N[v]]),(y||_)&&k+d<F[v]+h&&F[v]+h<S+u&&(c=!0),F[v]!=k&&(y=!1),F[v]!=S&&(_=!1));if(!r)for(v=t+1;v<4;v++)0<g[v]&&(l=se[a]),T[v]<ae[N[v]]&&(w=se[a]);c||(k=A(g[t],se[a],te[a],ae[a])+l,S=A(T[t],se[a],te[a],ae[a])-w,y&&(p=k),_&&(b=S))}if(y||_||c)for(v=p;v<=b;v+=se[a])o[v]=!r}}}var E,b,n,C={},o={},v={},F=[],l=function(e){var t,a,s,n={};if(e.is("input")){switch(e.attr("type")){case"date":t="yy-mm-dd";break;case"datetime":t="yy-mm-ddTHH:ii:ssZ";break;case"datetime-local":t="yy-mm-ddTHH:ii:ss";break;case"month":t="yy-mm",n.dateOrder="mmyy";break;case"time":t="HH:ii:ss"}n.format=t,a=e.attr("min"),s=e.attr("max"),a&&"undefined"!=a&&(n.min=le(t,a)),s&&"undefined"!=s&&(n.max=le(t,s))}return n}(pa(this)),c=va({},x.settings),m=ie[c.calendarSystem],H=va(x.settings,he,m,Ne,l,c),k=H.preset,d="datetime"==k?H.dateFormat+H.separator+H.timeFormat:"time"==k?H.timeFormat:H.dateFormat,S=l.format||d,D=H.dateWheels||H.dateFormat,V=H.timeWheels||H.timeFormat,N=H.dateWheels||H.dateDisplay,P=V,I=H.baseTheme||H.theme,L=e(H.invalid),O=e(H.valid),Y=at(H.min,S,H),z=at(H.max,S,H),$=/time/i.test(k),R=/h/.test(P),j=/D/.test(N),W=H.steps||{},J=W.hour||H.stepHour||1,U=W.minute||H.stepMinute||1,q=W.second||H.stepSecond||1,B=W.zeroBased,K=B||!Y?0:Y.getHours()%J,G=B||!Y?0:Y.getMinutes()%U,X=B||!Y?0:Y.getSeconds()%q,Z=t(J,K,R?11:23),Q=t(U,G,59),ee=t(U,G,59),te={y:Y?Y.getFullYear():-1/0,m:0,d:1,h:K,i:G,s:X,a:0,tt:0},ae={y:z?z.getFullYear():1/0,m:11,d:31,h:Z,i:Q,s:ee,a:1,tt:86400},se={y:1,m:1,d:1,h:J,i:U,s:q,a:1,tt:1},ne={y:function(e){return H.getYear(e)},m:function(e){return H.getMonth(e)},d:function(e){return H.getDay(e)},h:function(e){var t=e.getHours();return A(t=R&&12<=t?t-12:t,J,K,Z)},i:function(e){return A(e.getMinutes(),U,G,Q)},s:function(e){return A(e.getSeconds(),q,X,ee)},u:function(e){return e.getMilliseconds()},a:function(e){return 11<e.getHours()?1:0},dd:i,tt:function(e){return A(Math.round((e.getTime()-new Date(e).setHours(0,0,0,0))/1e3),E||1,0,86400)}};return x.getVal=function(e){return x._hasValue||e?ce(g(x.getArrayVal(e)),H,S):null},x.getDate=function(e){return x._hasValue||e?g(x.getArrayVal(e)):null},x.setDate=function(e,t,a,s,n){x.setArrayVal(T(e,!0),t,n,s,a)},n=function(){var e,t,a,s,n,i,o,r,l=0,c=[],m=[],d=[];if(/date/i.test(k)){for(e=D.split(/\|/.test(D)?"|":""),s=0;s<e.length;s++)if(i=0,(a=e[s]).length)if(/y/i.test(a)&&(v.y=1,i++),/m/i.test(a)&&(v.y=1,v.m=1,i++),/d/i.test(a)&&(v.y=1,v.m=1,v.d=1,i++),1<i&&void 0===C.dd)C.dd=l,l++,m.push(f(a)),d=m,b=!0;else if(/y/i.test(a)&&void 0===C.y)C.y=l,l++,m.push({cssClass:"mbsc-dt-whl-y",label:H.yearText,min:Y?H.getYear(Y):void 0,max:z?H.getYear(z):void 0,data:u,getIndex:h});else if(/m/i.test(a)&&void 0===C.m){for(C.m=l,o=[],l++,n=0;n<12;n++)r=N.replace(/[dy|]/gi,"").replace(/mm/,oe(n+1)+(H.monthSuffix||"")).replace(/m/,n+1+(H.monthSuffix||"")),o.push({value:n,display:/MM/.test(r)?r.replace(/MM/,'<span class="mbsc-dt-month">'+H.monthNames[n]+"</span>"):r.replace(/M/,'<span class="mbsc-dt-month">'+H.monthNamesShort[n]+"</span>")});m.push({cssClass:"mbsc-dt-whl-m",label:H.monthText,data:o})}else if(/d/i.test(a)&&void 0===C.d){for(C.d=l,o=[],l++,n=1;n<32;n++)o.push({value:n,display:(/dd/i.test(N)?oe(n):n)+(H.daySuffix||"")});m.push({cssClass:"mbsc-dt-whl-d",label:H.dayText,data:o})}c.push(m)}if(/time/i.test(k)){for(t=V.split(/\|/.test(V)?"|":""),s=0;s<t.length;s++)if(i=0,(a=t[s]).length&&(/h/i.test(a)&&(v.h=1,i++),/i/i.test(a)&&(v.i=1,i++),/s/i.test(a)&&(v.s=1,i++),/a/i.test(a)&&i++),1<i&&void 0===C.tt)C.tt=l,l++,d.push(p(a));else if(/h/i.test(a)&&void 0===C.h){for(o=[],C.h=l,v.h=1,l++,n=K;n<(R?12:24);n+=J)o.push({value:n,display:R&&0===n?12:/hh/i.test(P)?oe(n):n});d.push({cssClass:"mbsc-dt-whl-h",label:H.hourText,data:o})}else if(/i/i.test(a)&&void 0===C.i){for(o=[],C.i=l,v.i=1,l++,n=G;n<60;n+=U)o.push({value:n,display:/ii/i.test(P)?oe(n):n});d.push({cssClass:"mbsc-dt-whl-i",label:H.minuteText,data:o})}else if(/s/i.test(a)&&void 0===C.s){for(o=[],C.s=l,v.s=1,l++,n=X;n<60;n+=q)o.push({value:n,display:/ss/i.test(P)?oe(n):n});d.push({cssClass:"mbsc-dt-whl-s",label:H.secText,data:o})}else/a/i.test(a)&&void 0===C.a&&(C.a=l,l++,d.push({cssClass:"mbsc-dt-whl-a",label:H.ampmText,data:/A/.test(a)?[{value:0,display:H.amText.toUpperCase()},{value:1,display:H.pmText.toUpperCase()}]:[{value:0,display:H.amText},{value:1,display:H.pmText}]}));d!=m&&c.push(d)}return c}(),H.isoParts=v,x._format=d,x._order=C,x.handlers.now=function(){x.setDate(new Date,x.live,1e3,!0,!0)},x.buttons.now={text:H.nowText,icon:H.nowIcon,handler:"now"},{minWidth:b&&$?{bootstrap:46,ios:50,material:46,mobiscroll:46,windows:50}[I]:void 0,compClass:"mbsc-dt mbsc-sc",wheels:n,headerText:!!H.headerText&&function(){return re(d,g(x.getArrayVal(!0)),H)},formatValue:function(e){return re(S,g(e),H)},parseValue:function(e){return e||(o={},x._hasValue=!1),T(at(e||H.defaultValue||new Date,S,H,v),!!e)},validate:function(e){var t,r,a,s,n=e.values,i=e.index,o=e.direction,l=H.wheels[0][C.d],c=function(e,t){var a,s,n=!1,i=!1,o=0,r=0,l=Y?g(T(Y)):-1/0,c=z?g(T(z)):1/0;if(_(e))return e;if(e<l&&(e=l),c<e&&(e=c),s=a=e,2!==t)for(n=_(a,!0);!n&&a<c&&o<100;)n=_(a=new Date(a.getTime()+864e5),!0),o++;if(1!==t)for(i=_(s,!0);!i&&l<s&&r<100;)i=_(s=new Date(s.getTime()-864e5),!0),r++;return 1===t&&n?a:2===t&&i?s:y(e,a)?a:y(e,s)?s:r<=o&&i?s:a}(g(n),o),m=T(c),d=[],u={},h=ne.y(c),f=ne.m(c),p=H.getMaxDayOfMonth(h,f),b=!0,v=!0;if(pa.each(["dd","y","m","d","tt","a","h","i","s"],function(e,a){var t=te[a],s=ae[a],n=ne[a](c);if(d[C[a]]=[],b&&Y&&(t=ne[a](Y)),v&&z&&(s=ne[a](z)),n<t&&(n=t),s<n&&(n=s),"dd"!==a&&"tt"!==a&&(b=b&&n==t,v=v&&n==s),void 0!==C[a]){if("y"!=a&&"dd"!=a)for(r=te[a];r<=ae[a];r+=se[a])(r<t||s<r)&&d[C[a]].push(r);if("d"==a){var i=H.getDate(h,f,1).getDay(),o={};w(L,h,f,i,p,o,1),w(O,h,f,i,p,o,0),pa.each(o,function(e,t){t&&d[C[a]].push(e)})}}}),$&&pa.each(["a","h","i","s","tt"],function(e,a){var t=ne[a](c),s=ne.d(c),n={};void 0!==C[a]&&(M(L,e,a,h,f,s,n,0),M(O,e,a,h,f,s,n,1),pa.each(n,function(e,t){t&&d[C[a]].push(e)}),F[e]=x.getValidValue(C[a],t,o,n))}),l&&(l._length!==p||j&&(void 0===i||i===C.y||i===C.m))){for((u[C.d]=l).data=[],t=1;t<=p;t++)s=H.getDate(h,f,t).getDay(),a=N.replace(/[my|]/gi,"").replace(/dd/,(t<10?"0"+t:t)+(H.daySuffix||"")).replace(/d/,t+(H.daySuffix||"")),l.data.push({value:t,display:/DD/.test(a)?a.replace(/DD/,'<span class="mbsc-dt-day">'+H.dayNames[s]+"</span>"):a.replace(/D/,'<span class="mbsc-dt-day">'+H.dayNamesShort[s]+"</span>")});x._tempWheelArray[C.d]=m[C.d],x.changeWheel(u)}return{disabled:d,valid:m}}}}function Ve(S){var p,b,v,a,s,x,l,n,o,e,D,g,T,i,y,_,r,V,c,w,M,N,C,d,A,k,E,m,F,H,P,u,I,h,L,O,Y,f,z,$,R,j,W,J,U,q,B,K,G,X,Z,Q,ee,te,ae,se,ne,ie,oe,re,le,ce,me,de,ue,he,fe,pe,be,t,ve,xe,ge=1,Te=this;function ye(e){e.hasClass("mbsc-cal-h")||e.addClass("mbsc-cal-h")}function _e(e){e.hasClass("mbsc-cal-h")?function(e){e.hasClass("mbsc-cal-h")&&(e.removeClass("mbsc-cal-h"),S._onSelectShow())}(e):ye(e)}function we(e,t,a){e[t]=e[t]||[],e[t].push(a)}function Me(e,a,s){var n,i,o,r,l=oe.getYear(a),c=oe.getMonth(a),m={};return e&&pa.each(e,function(e,t){if(n=t.d||t.start||t,i=n+"",t.start&&t.end)for(r=et(at(t.start,d,oe)),o=et(at(t.end,d,oe));r<=o;)we(m,r,t),r=oe.getDate(oe.getYear(r),oe.getMonth(r),oe.getDay(r)+1);else if(it.test(i))for(r=We(a,!1,+i.replace("w",""));r<=s;)we(m,r,t),r=oe.getDate(oe.getYear(r),oe.getMonth(r),oe.getDay(r)+7);else if(nt.test(i))if((i=i.split("/"))[1])for(r=oe.getDate(l,i[0]-1,i[1]);r<=s;)we(m,r,t),r=oe.getDate(oe.getYear(r)+1,oe.getMonth(r),i[1]);else for(r=oe.getDate(l,c,i[0]);r<=s;)we(m,r,t),r=oe.getDate(oe.getYear(r),oe.getMonth(r)+1,i[0]);else we(m,et(at(n,d,oe)),t)}),m}function Ce(e){var t,a,s,n,i=!!f[e]&&f[e],o=!!z[e]&&z[e],r=o&&o[0].background?o[0].background:i&&i[0].background,l="";if(o)for(t=0;t<o.length;t++)l+=(o[t].cssClass||"")+" ";if(i){for(s='<div class="mbsc-cal-marks">',t=0;t<i.length;t++)l+=((a=i[t]).cssClass||"")+" ",s+='<div class="mbsc-cal-mark"'+(a.color?' style="background:'+a.color+';"':"")+"></div>";s+="</div>"}return n={marked:i,background:r,cssClass:l,markup:M[e]?M[e].join(""):m?s:""},va(n,S._getDayProps(e,n))}function ke(e){return' style="'+(O?"transform: translateY("+100*e+"%)":"left:"+100*e*ie+"%")+'"'}function Se(e){return Je(e,ae-1)>$&&(e=Je($,1-ae)),e<U&&(e=U),e}function De(e,t,a){var s=e.color,n=e.text;return'<div data-id="'+e._id+'" data-index="'+t+'" class="mbsc-cal-txt" title="'+pa("<div>"+n+"</div>").text()+'"'+(s?' style="background:'+s+(a?";color:"+ot(s):"")+';"':"")+">"+(a?n:"")+"</div>"}function Ve(e){var t=We(Je(e,-se-te),!1),a=We(Je(e,-se+ae+te-1),!1);a=oe.getDate(oe.getYear(a),oe.getMonth(a),oe.getDay(a)+7*D),S._onGenMonth(t,a),P=Me(oe.invalid,t,a),ce=Me(oe.valid,t,a),f=Me(oe.labels||oe.events||oe.marked,t,a),z=Me(oe.colors,t,a),Y=S._labels||f||z,(E=oe.labels||S._labels)&&function(){M={};for(var x={},g=t,e=function(){g.getDay()==A&&(x={});for(var e=j,t=Y[g]||[],a=t.length,s=[],n=void 0,i=void 0,o=0,r=0,l=0,c=void 0;o<e;)if(n=null,t.forEach(function(e,t){x[o]==e&&(n=e,i=t)}),o==e-1&&(r<a-1||a&&l==a&&!n)){var m=a-r,d=(1<m&&oe.moreEventsPluralText||oe.moreEventsText).replace(/{count}/,m);m&&s.push('<div class="mbsc-cal-txt-more">'+d+"</div>"),n&&(x[o]=null,n._days.forEach(function(e){M[e][o]='<div class="mbsc-cal-txt-more">'+oe.moreEventsText.replace(/{count}/,1)+"</div>"})),r++,o++}else if(n)i==l&&l++,st(g,at(n.end))&&(x[o]=null),s.push(De(n,i)),o++,r++,n._days.push(g);else if(l<a){var u=t[l],h=u.start&&at(u.start),f=u.end&&at(u.end),p=g.getDay(),b=0<A-p?7:0,v=f&&!st(h,f);h&&!st(g,h)&&p!=A||(void 0===u._id&&(u._id=ge++),v&&(x[o]=u),u._days=[g],c=v?100*Math.min(Qe(g,et(f))+1,7+A-p-b):100,s.push(v?'<div class="mbsc-cal-txt-w" style="width:'+c+'%">'+De(u,l,!0)+"</div>"+De(u,l):De(u,l,!0)),o++,r++),l++}else s.push('<div class="mbsc-cal-txt-ph"></div>'),o++;M[g]=s,g=oe.getDate(oe.getYear(g),oe.getMonth(g),oe.getDay(g)+1)};g<a;)e()}()}function Ne(e){var t=oe.getYear(e),a=oe.getMonth(e);Ye(o=w=e),le("onMonthChange",{year:t,month:a}),le("onMonthLoading",{year:t,month:a}),le("onPageChange",{firstDay:e}),le("onPageLoading",{firstDay:e}),Ve(e)}function Ae(e){var t=oe.getYear(e),a=oe.getMonth(e);void 0===ee?Ee(e,t,a):Pe(e,ee,!0),Ie(o,C.focus),C.focus=!1}function Ee(e,t,a){var s=C.$scroller;pa(".mbsc-cal-slide",s).removeClass("mbsc-cal-slide-a"),pa(".mbsc-cal-slide",s).slice(te,te+ae).addClass("mbsc-cal-slide-a"),E&&pa(".mbsc-cal-slide-a .mbsc-cal-txt",s).on("mouseenter",function(){var e=pa(this).attr("data-id");pa('.mbsc-cal-txt[data-id="'+e+'"]',s).addClass("mbsc-hover")}).on("mouseleave",function(){pa(".mbsc-cal-txt.mbsc-hover",s).removeClass("mbsc-hover")}),le("onMonthLoaded",{year:t,month:a}),le("onPageLoaded",{firstDay:e})}function Fe(e,t){var a,s=oe.getYear(e),n='<div class="mbsc-cal-slide"'+ke(t)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">';for(a=0;a<12;a++)a&&a%3==0&&(n+='</div><div class="mbsc-cal-row">'),n+='<div role="gridcell" tabindex="-1" aria-label="'+s+'" data-val="'+s+'" class="mbsc-cal-cell mbsc-btn-e '+(s<K||J<s?" mbsc-disabled ":"")+(s==oe.getYear(w)?V:"")+'"><div class="mbsc-cal-cell-i mbsc-cal-cell-txt">'+s+be+"</div></div>",s++;return n+="</div></div></div>"}function He(e,t){var a,s,n,i,o,r,l,c,m,d,u,h,f,p,b,v,x,g=1,T=oe.getYear(e),y=oe.getMonth(e),_=oe.getDay(e),w=null!==oe.defaultValue||S._hasValue?S.getDate(!0):null,M=oe.getDate(T,y,_).getDay(),C=0<A-M?7:0,k='<div class="mbsc-cal-slide"'+ke(t)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">';for(v=0;v<7*D;v++)b=v+A-C,n=(a=oe.getDate(T,y,b-M+_)).getFullYear(),i=a.getMonth(),o=a.getDate(),r=oe.getMonth(a),l=oe.getDay(a),p=oe.getMaxDayOfMonth(n,i),c=n+"-"+(i+1)+"-"+o,d=(m=va({valid:(x=a,!(x<U||$<x||void 0!==P[x]&&void 0===ce[x])),selected:w&&st(w,a)},Ce(a))).valid,u=m.selected,s=m.cssClass,h=new Date(a).setHours(12,0,0,0)===(new Date).setHours(12,0,0,0),f=r!==y,N[c]=m,v&&v%7==0&&(k+='</div><div class="mbsc-cal-row">'),de&&v%7==0&&("month"==de&&f&&1<g?g=1==o?1:2:"year"==de&&(g=oe.getWeekNumber(oe.getDate(n,i,o+(7-A+1)%7))),k+='<div role="gridcell" class="mbsc-cal-cell mbsc-cal-week-nr">'+g+"</div>",g++),k+='<div role="gridcell" aria-label="'+(h?oe.todayText+", ":"")+oe.dayNames[a.getDay()]+", "+oe.monthNames[r]+" "+l+" "+(m.ariaLabel?", "+m.ariaLabel:"")+'"'+(f&&!re?' aria-hidden="true"':' data-full="'+c+'"')+(u?' aria-selected="true"':"")+(d?' tabindex="-1"':' aria-disabled="true"')+' class="mbsc-cal-cell mbsc-cal-day mbsc-cal-day'+b%7+" "+(oe.dayClass||"")+" "+(u?V:"")+(h?" "+oe.todayClass:"")+(s?" "+s:"")+(1==l?" mbsc-cal-day-first":"")+(l==p?" mbsc-cal-day-last":"")+(f?" mbsc-cal-day-diff":"")+(d?" mbsc-btn-e":" mbsc-disabled")+(m.marked?" mbsc-cal-day-marked":"")+(m.background?" mbsc-cal-day-colored":"")+'"><div class="mbsc-cal-cell-i mbsc-cal-day-i"><div class="mbsc-cal-day-date mbsc-cal-cell-txt"'+(m.background?' style="background:'+m.background+";color:"+ot(m.background)+'"':"")+">"+l+"</div>"+(m.markup?"<div>"+m.markup+"</div>":"")+"</div></div>";return k+="</div></div></div>"}function Pe(e,t,a){var s,n=oe.getYear(e),i=oe.getMonth(e),o=C?C.pos:0,r="";if(N={},D)for(t||(le("onMonthLoading",{year:n,month:i}),le("onPageLoading",{firstDay:e})),Ve(e),s=0;s<ne;s++)r+=He(Je(e,s-se-te),o*ie+s-te);return ee=void 0,a&&C&&(C.$active=null,C.$scroller.html(r),Ee(e,n,i)),r}function Ie(e,t){if(C){var a=C.$active;a&&a.length&&(a[0].blur(),a.hasClass("mbsc-disabled")?a.removeAttr("tabindex"):a.attr("tabindex","-1")),C.$active=pa('.mbsc-cal-slide-a .mbsc-cal-day[data-full="'+tt(e)+'"]',C.$scroller).attr("tabindex","0"),t&&C.$active.length&&C.$active[0].focus()}}function Le(e,t){pa(".mbsc-selected",t).removeClass(V).removeAttr("aria-selected"),pa('.mbsc-cal-cell[data-val="'+e+'"]',t).addClass(V).attr("aria-selected","true")}function Oe(e,t,a,s){var n,i;me&&(e<U&&(e=U),$<e&&(e=$),"calendar"!==me&&F&&!t||(S._isSetDate=!t,k&&D&&(i=We(Se(e),h),Q&&(e<Je(w,-se)||e>=Je(w,ae-se))&&(n=h?oe.getMonth(i)-oe.getMonth(w)+12*(oe.getYear(i)-oe.getYear(w)):Math.floor(Qe(w,i)/(7*D)))&&(C.queue=[],C.focus=s&&a,qe(C,n,a)),n&&a||Ie(e,s),t||function(e){var t=C&&C.$scroller;oe.highlight&&C&&(pa(".mbsc-selected",t).removeClass(V).removeAttr("aria-selected"),null===oe.defaultValue&&!S._hasValue||pa('.mbsc-cal-day[data-full="'+tt(e)+'"]',t).addClass(V).attr("aria-selected","true"))}(e),h||Ye(e,!0),o=e,Q=!0),S._onSetDate(e,n),S._isSetDate=!1))}function Ye(e,t){var a,s,n,i=oe.getYear(e),o=oe.getMonth(e),r=i+be;if(H){if(Le(o,Z.$scroller),Le(i,pe.$scroller),qe(pe,Math.floor(i/12)-Math.floor(oe.getYear(pe.first)/12),!0),pa(".mbsc-cal-cell",Z.$scroller).removeClass("mbsc-disabled"),i===K)for(a=0;a<B;a++)pa('.mbsc-cal-cell[data-val="'+a+'"]',Z.$scroller).addClass("mbsc-disabled");if(i===J)for(a=W+1;a<=12;a++)pa('.mbsc-cal-cell[data-val="'+a+'"]',Z.$scroller).addClass("mbsc-disabled")}for(t||(ze(pa(".mbsc-cal-prev-m",b),Je(e,-se)<=U),ze(pa(".mbsc-cal-next-m",b),Je(e,ae-se)>$),ze(pa(".mbsc-cal-prev-y",b),oe.getDate(i-1,o+1,1)<=U),ze(pa(".mbsc-cal-next-y",b),oe.getDate(i+1,o,1)>$)),l.attr("aria-label",i).html(r),a=0;a<ae;a++)e=oe.getDate(i,o-se+a,1),s=oe.getYear(e),n=oe.getMonth(e),r=s+be,v.eq(a).attr("aria-label",oe.monthNames[n]+(he?"":" "+i)).html((!he&&fe<G?r+" ":"")+X[n]+(!he&&G<fe?" "+r:""))}function ze(e,t){t?e.addClass(r).attr("aria-disabled","true"):e.removeClass(r).removeAttr("aria-disabled")}function $e(e,t){var a=S.getDate(!0),s=e[0],n=e.attr("data-full"),i=n?n.split("-"):[],o=Ze(i[0],i[1]-1,i[2]),r=Ze(o.getFullYear(),o.getMonth(),o.getDate(),a.getHours(),a.getMinutes(),a.getSeconds()),l=e.hasClass("mbsc-selected"),c=pa(t.target),m=c[0];if(re||!e.hasClass("mbsc-cal-day-diff")){if(E&&s.contains(m))for(;m!=s;){if(c.hasClass("mbsc-cal-txt")||c.hasClass("mbsc-cal-txt-more")){var d=c.attr("data-index"),u=Y[o];if(!1===le("onLabelTap",{date:r,domEvent:t,target:c[0],labels:u,label:u[d]}))return;break}m=(c=c.parent())[0]}!1===le("onDayChange",va(N[n],{date:r,target:s,selected:l}))||oe.readonly||e.hasClass("mbsc-disabled")||S._selectDay(e,o,r,l)}}function Re(e){ye(a),Oe(oe.getDate(oe.getYear(C.first),e.attr("data-val"),1),!0,!0)}function je(e){ye(n),Oe(oe.getDate(e.attr("data-val"),oe.getMonth(C.first),1),!0,!0)}function We(e,t,a){var s=oe.getYear(e),n=oe.getMonth(e),i=e.getDay(),o=0<A-i?7:0;return t?oe.getDate(s,n,1):oe.getDate(s,n,(void 0===a?A:a)-o-i+oe.getDay(e))}function Je(e,t){var a=oe.getYear(e),s=oe.getMonth(e),n=oe.getDay(e);return h?oe.getDate(a,s+t,1):oe.getDate(a,s,n+t*D*7)}function Ue(e,t){var a=12*Math.floor(oe.getYear(e)/12);return oe.getDate(a+12*t,0,1)}function qe(e,t,a,s){t&&S._isVisible&&(e.queue.push(arguments),1==e.queue.length&&function s(n,i,e,o){var r,l,t="",c=n.$scroller,m=n.buffer,d=n.offset,a=n.pages,u=n.total,h=n.first,f=n.genPage,p=n.getFirst,b=0<i?Math.min(i,m):Math.max(i,-m),v=n.pos*ie+b-i+d,x=Math.abs(i)>m;n.callback&&(n.load(),n.callback(!0));n.first=p(h,i);n.pos+=b*ie;n.changing=!0;n.load=function(){if(x){for(r=0;r<a;r++)t+=f(p(h,l=i+r-d),v+l);0<i?(pa(".mbsc-cal-slide",c).slice(-a).remove(),c.append(t)):i<0&&(pa(".mbsc-cal-slide",c).slice(0,a).remove(),c.prepend(t))}};n.callback=function(e){var t=Math.abs(b),a="";if(S._isVisible){for(r=0;r<t;r++)a+=f(p(h,l=i+r-d-m+(0<i?u-t:0)),v+l);if(0<i?(c.append(a),pa(".mbsc-cal-slide",c).slice(0,b).remove()):i<0&&(c.prepend(a),pa(".mbsc-cal-slide",c).slice(b).remove()),x){for(a="",r=0;r<t;r++)a+=f(p(h,l=i+r-d-m+(0<i?0:u-t)),v+l);0<i?(pa(".mbsc-cal-slide",c).slice(0,b).remove(),c.prepend(a)):i<0&&(pa(".mbsc-cal-slide",c).slice(b).remove(),c.append(a))}Ke(n),o&&!e&&o(),n.callback=null,n.load=null,n.queue.shift(),x=!1,n.queue.length?s.apply(this,n.queue[0]):(n.changing=!1,n.onAfterChange(n.first))}};n.onBeforeChange(n.first);n.load&&(n.load(),n.scroller.scroll(-n.pos*n.size,e?200:0,!1,n.callback))}(e,t,a,s))}function Be(e,t,a,s,n,i,o,r,l,c,m,d,u){var h=O?"Y":"X",f={$scroller:pa(".mbsc-cal-scroll",e),queue:[],buffer:s,offset:n,pages:i,first:r,total:o,pos:0,min:t,max:a,genPage:d,getFirst:u,onBeforeChange:c,onAfterChange:m};return f.scroller=new lt(e,{axis:h,easing:"",contSize:0,maxSnapScroll:s,mousewheel:void 0===oe.mousewheel?O:oe.mousewheel,time:200,lock:!0,rtl:L,stopProp:!1,minScroll:0,maxScroll:0,onBtnTap:function(e){"touchend"==e.domEvent.type&&da(),l(pa(e.target),e.domEvent)},onAnimationStart:function(){f.changing=!0},onAnimationEnd:function(e){d&&qe(f,Math.round((-f.pos*f.size-e["pos"+h])/f.size)*ie)}}),S._scrollers.push(f.scroller),f}function Ke(e,t){var a,s=0,n=0,i=e.first;if(!e.changing||!t){if(e.getFirst){for(s=e.buffer,n=e.buffer;n&&e.getFirst(i,n+e.pages-e.offset-1)>e.max;)n--;for(;s&&e.getFirst(i,1-s-e.offset)<=e.min;)s--}a=Math.round(g/e.pages),I&&a&&e.size!=a&&e.$scroller[O?"height":"width"](a),va(e.scroller.settings,{snap:a,minScroll:(-e.pos*ie-n)*a,maxScroll:(-e.pos*ie+s)*a}),e.size=a,e.scroller.refresh()}}function Ge(e){S._onRefresh(e),S._isVisible&&k&&D&&(C&&C.changing?ee=e:(Pe(w,e,!0),Ie(o)))}return y={},_=[],M={},le=S.trigger,xe=va({},S.settings),t=(oe=va(S.settings,mt,xe)).controls.join(","),A=oe.firstDay,L=oe.rtl,te=oe.pageBuffer,de=oe.weekCounter,D=oe.weeks,h=6==D,O="vertical"==oe.calendarScroll,i=S._getRespCont(),ue="full"==oe.weekDays?"":"min"==oe.weekDays?"Min":"Short",ve=oe.layout||("inline"==oe.display||/top|bottom/.test(oe.display)&&oe.touchUi?"liquid":""),T=(I="liquid"==ve)?null:oe.calendarWidth,ie=L&&!O?-1:1,r="mbsc-disabled "+(oe.disabledClass||""),c="mbsc-selected "+(oe.selectedTabClass||""),V="mbsc-selected "+(oe.selectedClass||""),j=Math.max(1,Math.floor(((oe.calendarHeight||0)/D-45)/18)),t.match(/calendar/)&&(y.calendar=1,k=!0),t.match(/date/)&&!k&&(y.date=1),t.match(/time/)&&(y.time=1),oe.controls.forEach(function(e){y[e]&&_.push(e)}),H=oe.quickNav&&k&&h,he=oe.yearChange&&h,I&&k&&"center"==oe.display&&(S._isFullScreen=!0),oe.layout=ve,oe.preset=(y.date||k?"date":"")+(y.time?"time":""),e=ct.call(this,S),X=he?oe.monthNamesShort:oe.monthNames,be=oe.yearSuffix||"",G=(oe.dateWheels||oe.dateFormat).search(/m/i),fe=(oe.dateWheels||oe.dateFormat).search(/y/i),d=S._format,oe.min&&(U=et(at(oe.min,d,oe)),K=oe.getYear(U),B=oe.getMonth(U),q=oe.getDate(12*Math.floor(K/12),0,1)),oe.max&&($=et(at(oe.max,d,oe)),J=oe.getYear($),W=oe.getMonth($),R=oe.getDate(12*Math.floor(J/12),0,1)),S.refresh=function(){Ge(!1)},S.redraw=function(){Ge(!0)},S.navigate=function(e,t){Oe(at(e,d,oe),!0,t)},S.changeTab=function(e){S._isVisible&&y[e]&&me!=e&&(me=e,pa(".mbsc-cal-tab",b).removeClass(c).removeAttr("aria-selected"),pa('.mbsc-cal-tab[data-control="'+e+'"]',b).addClass(c).attr("aria-selected","true"),F&&(x.addClass("mbsc-cal-h"),y[me].removeClass("mbsc-cal-h")),"calendar"==me&&Oe(S.getDate(!0),!1,!0),S._showDayPicker(),S.trigger("onTabChange",{tab:me}))},S._checkSize=!0,S._onGenMonth=ra,S._onSelectShow=ra,S._onSetDate=ra,S._onRefresh=ra,S._getDayProps=ra,S._prepareObj=Me,S._showDayPicker=function(){H&&(ye(n),ye(a))},S._selectDay=S.__selectDay=function(e,t,a){var s=S.live;Q=oe.outerMonthChange,u=!0,S.setDate(a,s,1e3,!s,!0),s&&le("onSet",{valueText:S._value})},va(e,{labels:null,compClass:"mbsc-calendar mbsc-dt mbsc-sc",onMarkupReady:function(e){var t=0;b=pa(e.target),s=pa(".mbsc-fr-c",b),o=S.getDate(!0),g=0,k&&(m=!(!oe.marked&&!oe.data||oe.labels||oe.multiLabel||oe.showEventCount),Q=!0,me="calendar",ae="auto"==oe.months?Math.max(1,Math.min(3,Math.floor((T||Xe(i))/280))):+oe.months,ne=ae+2*te,O=O&&ae<2,re=void(se=0)===oe.showOuterDays?ae<2&&!O:oe.showOuterDays,w=We(Se(o),h),s.append(function(){var e,t,a,s,n,i,o="",r=L?oe.btnCalNextClass:oe.btnCalPrevClass,l=L?oe.btnCalPrevClass:oe.btnCalNextClass;for(n='<div class="mbsc-cal-btn-w"><div data-step="-1" role="button" tabindex="0" aria-label="'+oe.prevMonthText+'" class="'+r+' mbsc-cal-prev mbsc-cal-prev-m mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div>',t=0;t<(D?ae:1);t++)n+='<div role="button" class="mbsc-cal-month"></div>';if(n+='<div data-step="1" role="button" tabindex="0" aria-label="'+oe.nextMonthText+'" class="'+l+' mbsc-cal-next mbsc-cal-next-m mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div></div>',he&&(o='<div class="mbsc-cal-btn-w"><div data-step="-12" role="button" tabindex="0" aria-label="'+oe.prevYearText+'" class="'+r+' mbsc-cal-prev mbsc-cal-prev-y mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div><div role="button" class="mbsc-cal-year"></div><div data-step="12" role="button" tabindex="0" aria-label="'+oe.nextYearText+'" class="'+l+' mbsc-cal-next mbsc-cal-next-y mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div></div>'),D&&(i=Pe(w)),e='<div class="mbsc-w-p mbsc-cal-c"><div class="mbsc-cal '+(h?"":" mbsc-cal-week-view")+(1<ae?" mbsc-cal-multi ":"")+(de?" mbsc-cal-weeks ":"")+(O?" mbsc-cal-vertical":"")+(m?" mbsc-cal-has-marks":"")+(E?" mbsc-cal-has-labels":"")+(re?"":" mbsc-cal-hide-diff ")+(oe.calendarClass||"")+'"'+(I?"":' style="width:'+(T||280*ae)+'px;"')+'><div class="mbsc-cal-hdr">'+(fe<G||1<ae?o+n:n+o)+"</div>",D){for(e+='<div class="mbsc-cal-body"><div class="mbsc-cal-day-picker"><div class="mbsc-cal-days-c">',a=0;a<ae;a++){for(e+='<div class="mbsc-cal-days">',t=0;t<7;t++)e+='<div class="mbsc-cal-week-day'+(s=(t+A)%7)+'" aria-label="'+oe.dayNames[s]+'">'+oe["dayNames"+ue][s]+"</div>";e+="</div>"}e+='</div><div class="mbsc-cal-scroll-c mbsc-cal-day-scroll-c '+(oe.calendarClass||"")+'"'+(oe.calendarHeight?' style="height:'+oe.calendarHeight+'px"':"")+'><div class="mbsc-cal-scroll" style="width:'+100/ae+'%">'+i+"</div></div>"}if(e+="</div>",H){for(e+='<div class="mbsc-cal-month-picker mbsc-cal-picker mbsc-cal-h"><div class="mbsc-cal-scroll-c '+(oe.calendarClass||"")+'"><div class="mbsc-cal-scroll">',t=0;t<3;t++){for(e+='<div class="mbsc-cal-slide"'+ke(t-1)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">',a=0;a<12;a++)a&&a%3==0&&(e+='</div><div class="mbsc-cal-row">'),e+='<div role="gridcell"'+(1==t?' tabindex="-1" aria-label="'+oe.monthNames[a]+'" data-val="'+a+'"':"")+' class="mbsc-cal-cell'+(1==t?" mbsc-btn-e":"")+'"><div class="mbsc-cal-cell-i mbsc-cal-cell-txt">'+(1==t?oe.monthNamesShort[a]:"&nbsp;")+"</div></div>";e+="</div></div></div>"}for(e+="</div></div></div>",e+='<div class="mbsc-cal-year-picker mbsc-cal-picker mbsc-cal-h"><div class="mbsc-cal-scroll-c '+(oe.calendarClass||"")+'"><div class="mbsc-cal-scroll">',t=-1;t<2;t++)e+=Fe(Ue(w,t),t);e+="</div></div></div>"}return e+="</div></div></div>"}()),v=pa(".mbsc-cal-month",b),l=pa(".mbsc-cal-year",b),p=pa(".mbsc-cal-day-scroll-c",b)),H&&(n=pa(".mbsc-cal-year-picker",b),a=pa(".mbsc-cal-month-picker",b)),x=pa(".mbsc-w-p",b),1<_.length&&s.before(function(){var a,s;return a='<div class="mbsc-cal-tabs-c"><div class="mbsc-cal-tabs" role="tablist">',_.forEach(function(e,t){s=oe[("calendar"==e?"date":e)+"Text"],a+='<div role="tab" aria-controls="'+Te.id+"-mbsc-pnl-"+t+'" class="mbsc-cal-tab mbsc-fr-btn-e '+(t?"":c)+'" data-control="'+e+'"'+(oe.tabLink?'><a href="#">'+s+"</a>":' tabindex="0">'+s)+"</div>"}),a+="</div></div>"}()),["date","time","calendar"].forEach(function(e){y[e]?(y[e]=x.eq(t),t++):"date"==e&&!y.date&&k&&(x.eq(t).remove(),t++)}),_.forEach(function(e){s.append(y[e])}),!k&&y.date&&y.date.css("position","relative"),S._scrollers=[],function(){if(k&&D){var e=pa(".mbsc-cal-scroll-c",b);C=Be(e[0],U,$,te,se,ae,ne,w,$e,Ne,Ae,He,Je),H&&(Z=Be(e[1],null,null,1,0,1,3,w,Re),pe=Be(e[2],q,R,1,0,1,3,w,je,ra,ra,Fe,Ue),S.tap(v,function(){_e(a),ye(n)}),S.tap(l,function(){_e(n),ye(a)})),rt(pa(".mbsc-cal-btn",b),function(e,t,a,s){qe(C,t,!0,s)}),Ae(w),null===oe.defaultValue&&!S._hasValue||S._multiple||(S._activeElm=C.$active[0]),p.on("keydown",function(e){var t,a=oe.getYear(o),s=oe.getMonth(o),n=oe.getDay(o);switch(e.keyCode){case 32:$e(C.$active,e);break;case 37:t=oe.getDate(a,s,n-1*ie);break;case 39:t=oe.getDate(a,s,n+1*ie);break;case 38:t=oe.getDate(a,s,n-7);break;case 40:t=oe.getDate(a,s,n+7);break;case 36:t=oe.getDate(a,s,1);break;case 35:t=oe.getDate(a,s+1,0);break;case 33:t=e.altKey?oe.getDate(a-1,s,n):h?oe.getDate(a,s-1,n):oe.getDate(a,s,n-7*D);break;case 34:t=e.altKey?oe.getDate(a+1,s,n):h?oe.getDate(a,s+1,n):oe.getDate(a,s,n+7*D)}t&&(e.preventDefault(),Oe(t,!0,!1,!0))})}S.tap(pa(".mbsc-cal-tab",b),function(){S.changeTab(pa(this).attr("data-control"))})}()},onShow:function(){k&&D&&Ye(h?w:o)},onHide:function(){S._scrollers.forEach(function(e){e.destroy()}),me=pe=Z=C=N=null},onValidated:function(e){var t,a,s=e.index,n=S._order;a=S.getDate(!0),u?t="calendar":void 0!==s&&(t=n.dd==s||n.d==s||n.m==s||n.y==s?"date":"time"),le("onSetDate",{date:a,control:t}),"time"!==t&&Oe(a,!1,!!e.time,u&&!S._multiple),u=!1},onPosition:function(e){var t,a,s,n,i,o,r,l=e.oldHeight,c=e.windowHeight;if(F=(e.hasTabs||!0===oe.tabs||!1!==oe.tabs&&I)&&1<_.length,I&&(e.windowWidth>=oe.breakPointMd?pa(e.target).addClass("mbsc-fr-md"):pa(e.target).removeClass("mbsc-fr-md")),F?(b.addClass("mbsc-cal-tabbed"),me=pa(".mbsc-cal-tab.mbsc-selected",b).attr("data-control"),x.addClass("mbsc-cal-h"),y[me].removeClass("mbsc-cal-h")):(b.removeClass("mbsc-cal-tabbed"),x.removeClass("mbsc-cal-h")),S._isFullScreen&&(p.height(""),r=c-(i=e.popup.offsetHeight)+p[0].offsetHeight,i<=c&&p.height(r)),E&&D&&c!=l){var m=r||p[0].offsetHeight,d=p.find(".mbsc-cal-txt-ph")[0],u=d.offsetTop,h=d.offsetHeight,f=Math.max(1,Math.floor((m/D-u)/(h+2)));j!=f&&(j=f,S.redraw())}if(k&&D){if(n=(o=I||O||F?p[0][O?"offsetHeight":"offsetWidth"]:T||280*ae)!=g,g=o,I&&n&&he)for(X=oe.maxMonthWidth>v[0].offsetWidth?oe.monthNamesShort:oe.monthNames,a=oe.getYear(w),s=oe.getMonth(w),t=0;t<ae;t++)v.eq(t).text(X[oe.getMonth(oe.getDate(a,s-se+t,1))]);n&&Ke(C,!0)}H&&n&&(Ke(Z,!0),Ke(pe,!0))}})}var Ne={separator:" ",dateFormat:"mm/dd/yy",dateDisplay:"MMddyy",timeFormat:"h:ii A",dayText:"Day",monthText:"Month",yearText:"Year",hourText:"Hours",minuteText:"Minutes",ampmText:"&nbsp;",secText:"Seconds",nowText:"Now",todayText:"Today"},mt={controls:["calendar"],firstDay:0,weekDays:"short",maxMonthWidth:170,breakPointMd:768,months:1,pageBuffer:1,weeks:6,highlight:!0,outerMonthChange:!0,quickNav:!0,yearChange:!0,tabs:"auto",todayClass:"mbsc-cal-today",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left6",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right6",dateText:"Date",timeText:"Time",todayText:"Today",fromText:"Start",toText:"End",moreEventsText:"{count} more",prevMonthText:"Previous Month",nextMonthText:"Next Month",prevYearText:"Previous Year",nextYearText:"Next Year"},Ae={};Me.calendar=function(l){function i(e){var t,a,s,n=null;if(b={},e&&e.length)for(a=0;a<e.length;a++)t=at(e[a],o,u,u.isoParts),n=n||t,b[Ze((s=t).getFullYear(),s.getMonth(),s.getDate())]=t;return n}function c(){l.redraw()}var s,m,o,d,t,e=va({},l.settings),u=va(l.settings,Ae,e),h="mbsc-selected "+(u.selectedClass||""),a=u.defaultValue,f="multiple"==u.select||1<u.select||"week"==u.selectType,p=la(u.select)?u.select:1/0,b={};return s=Ve.call(this,l),d=void 0===u.firstSelectDay?u.firstDay:u.firstSelectDay,o=l._format,f&&i(a),l._multiple=f,l._getDayProps=function(e){return{selected:f?void 0!==b[e]:void 0}},l._selectDay=function(e,t,a,s){if(u.setOnDayTap&&"multiple"!=u.select&&"inline"!=u.display)return l.setDate(a),void l.select();if(f)if("week"==u.selectType){var n,i,o=t.getDay()-d;for(o=o<0?7+o:o,"multiple"!=u.select&&(b={}),n=0;n<7;n++)i=Ze(t.getFullYear(),t.getMonth(),t.getDate()-o+n),s?delete b[i]:te(b).length/7<p&&(b[i]=i);c()}else{var r=pa('.mbsc-cal-day[data-full="'+e.attr("data-full")+'"]',m);s?(r.removeClass(h).removeAttr("aria-selected"),delete b[t]):te(b).length<p&&(r.addClass(h).attr("aria-selected","true"),b[t]=t)}l.__selectDay(e,t,a)},l.setVal=function(e,t,a,s,n){f&&(e=i(e)),l._setVal(e,t,a,s,n),f&&c()},l.getVal=function(e){var t,a=[];if(f){for(t in b)a.push(ce(b[t],u,o));return a}return ce(l.getDate(e),u,o)},va({},s,{highlight:!f,outerMonthChange:!f,parseValue:function(e){return f&&e&&"string"==typeof e&&(e=i(e.split(","))),f&&a&&a.length&&(u.defaultValue=a[0]),s.parseValue.call(this,e)},formatValue:function(e){var t,a=[];if(f){for(t in b)a.push(re(o,b[t],u));return a.join(", ")}return s.formatValue.call(this,e,l)},onClear:function(){f&&(b={},c())},onBeforeShow:function(){void 0!==u.setOnDayTap||u.buttons&&u.buttons.length||1!=u.controls.length||(u.setOnDayTap=!0),u.setOnDayTap&&"inline"!=u.display&&(u.outerMonthChange=!1),u.counter&&f&&(u.headerText=function(){var e=0,t="week"==u.selectType?7:1;return pa.each(b,function(){e++}),(1<(e=Math.round(e/t))&&u.selectedPluralText||u.selectedText).replace(/{count}/,e)})},onMarkupReady:function(e){s.onMarkupReady.call(this,e),m=pa(e.target),f&&(pa(".mbsc-fr-hdr",m).attr("aria-live","off"),t=va({},b))},onCancel:function(){!l.live&&f&&(b=va({},t))}})},f("calendar",De);var Ee,Fe="mbsc-input-wrap",He=["touchstart","touchmove","touchend","touchcancel","mousedown","mousemove","mouseup","mouseleave"],Pe={tap:W};function Ie(e,t){var a={},s=e[0],n=e.parent(),i=n.find(".mbsc-err-msg"),o=e.attr("data-icon-align")||"left",r=e.attr("data-icon");n.hasClass(Fe)?n=n.parent():pa('<span class="'+Fe+'"></span>').insertAfter(e).append(e),i&&n.find("."+Fe).append(i),r&&(-1!==r.indexOf("{")?a=JSON.parse(r):a[o]=r),"file"==s.type&&(a.right=e.attr("data-icon-upload")||"upload"),(r||t)&&(va(a,t),n.addClass((a.right?"mbsc-ic-right ":"")+(a.left?" mbsc-ic-left":"")).find("."+Fe).append('<span class="mbsc-input-fill"></span>').append(a.left?'<span class="mbsc-input-ic mbsc-left-ic mbsc-ic mbsc-ic-'+a.left+'"></span>':"").append(a.right?'<span class="mbsc-input-ic mbsc-right-ic mbsc-ic mbsc-ic-'+a.right+'"></span>':""))}function Le(e,t,a,s,n){"segmented"==t?e.closest(".mbsc-segmented").addClass("box"==a?"mbsc-input-box":"").addClass("outline"==a?"mbsc-input-outline":""):"button"!=t&&"submit"!=t&&(e.addClass("mbsc-control-w").addClass("box"==a?"mbsc-input-box":"").addClass("outline"==a?"mbsc-input-outline":"").addClass("inline"==s?"mbsc-label-inline":"").addClass("stacked"==s?"mbsc-label-stacked":"").addClass("floating"==s?"mbsc-label-floating":"").addClass("floating"==s&&n.value?"mbsc-label-floating-active":"").find("label").addClass("mbsc-label").each(function(e,t){pa(t).attr("title",pa(t).text())}),e.contents().filter(function(){return 3==this.nodeType&&this.nodeValue&&/\S/.test(this.nodeValue)}).each(function(){pa('<span class="mbsc-label" title="'+this.textContent.trim()+'"></span>').insertAfter(this).append(this)}))}function Oe(e){var t=Z.themes.form[e];return t&&t.addRipple?t:null}function Ye(e,t,a){var s=e.attr(t);return void 0===s||""===s?a:s}function ze(e){var t=Z.themes.form[e.theme].baseTheme;return"mbsc-"+e.theme+(t?" mbsc-"+t:"")+(e.rtl?" mbsc-rtl":" mbsc-ltr")}var $e=function(){function d(e,t){var a=this;u(this,d);var s=va({},Pe,Z.settings,t),n=pa(e),i=n.parent(),o=i.hasClass("mbsc-input-wrap")?i.parent():i,r=n.next().hasClass("mbsc-fr")?n.next():null,l=ha(n),c=Ye(n,"data-input-style",s.inputStyle),m=Ye(n,"data-label-style",s.labelStyle);e.mbscInst&&e.mbscInst.destroy(),r&&r.insertAfter(o),s.theme=F(s),void 0===s.rtl&&s.lang&&Z.i18n[s.lang]&&(s.rtl=Z.i18n[s.lang].rtl),Le(o,l,c,m,e),n.addClass("mbsc-control"),this._handle=this._handle.bind(this),He.forEach(function(e){n.on(e,a._handle)}),this.settings=s,this._type=l,this._elm=e,this._$elm=n,this._$parent=o,this._$frame=r,this._ripple=Oe(s.theme),this._isFloating="floating"==m||o.hasClass("mbsc-label-floating"),this.cssClass=ze(s),this.getClassElm().addClass(this.cssClass),e.mbscInst=this}return t(d,[{key:"getClassElm",value:function(){return this._$parent}},{key:"destroy",value:function(){var t=this;this._$elm.removeClass("mbsc-control"),this.getClassElm().removeClass(this.cssClass),He.forEach(function(e){t._$elm.off(e,t._handle)}),delete this._elm.mbscInst}},{key:"option",value:function(e){va(this.settings,e);var t=this.getClassElm();this.cssClass&&t.removeClass(this.cssClass),this.cssClass=ze(this.settings),t.addClass(this.cssClass),this._ripple=Oe(this.settings.theme)}},{key:"_handle",value:function(e){switch(e.type){case"touchstart":case"mousedown":this._onStart(e);break;case"touchmove":case"mousemove":this._onMove(e);break;case"touchend":case"touchcancel":case"mouseup":case"mouseleave":this._onEnd(e)}}},{key:"_addRipple",value:function(e){this._ripple&&this._$rippleElm&&this._ripple.addRipple(this._$rippleElm,e)}},{key:"_removeRipple",value:function(){this._ripple&&this._$rippleElm&&this._ripple.removeRipple()}},{key:"_onStart",value:function(e){var t=this._elm;ga(e,t)&&(this._startX=ua(e,"X"),this._startY=ua(e,"Y"),Ee&&Ee.removeClass("mbsc-active"),t.disabled||(this._isActive=!0,(Ee=this._$elm).addClass("mbsc-active"),this._addRipple(e))),"touchstart"==e.type&&this._$elm.closest(".mbsc-no-touch").removeClass("mbsc-no-touch")}},{key:"_onMove",value:function(e){(this._isActive&&9<Math.abs(ua(e,"X")-this._startX)||9<Math.abs(ua(e,"Y")-this._startY))&&(this._$elm.removeClass("mbsc-active"),this._removeRipple(),this._isActive=!1)}},{key:"_onEnd",value:function(e){var t=this,a=this._elm,s=this._type;this._isActive&&this.settings.tap&&"touchend"==e.type&&!a.readOnly&&fa(a,s,e),this._isActive&&setTimeout(function(){t._$elm.removeClass("mbsc-active"),t._removeRipple()},100),this._isActive=!1,Ee=null}}]),d}();Z.themes.form.mobiscroll={};var Re=["focus","change","blur","animationstart"],je=function(){function r(e,t){var a;u(this,r);var s=(a=m(this,l(r).call(this,e,t)))._$elm,n=a._$parent,i=n.find(".mbsc-select-input, .mbsc-color-input");if(!function(e,t,a){var s={},n=a[0],i=a.attr("data-password-toggle"),o=a.attr("data-icon-show")||"eye",r=a.attr("data-icon-hide")||"eye-blocked";i&&(s.right="password"==n.type?o:r),Ie(a,s),i&&A(e,t.find(".mbsc-right-ic").addClass("mbsc-input-toggle"),function(){"text"==n.type?(n.type="password",pa(this).addClass("mbsc-ic-"+o).removeClass("mbsc-ic-"+r)):(n.type="text",pa(this).removeClass("mbsc-ic-"+o).addClass("mbsc-ic-"+r))})}(c(a),n,s),a._checkLabel=a._checkLabel.bind(c(a)),a._mouseDown=a._mouseDown.bind(c(a)),a._setText=a._setText.bind(c(a)),"file"==e.type){var o=n.find(".mbsc-file-input");a._$input=o.length?o:pa('<input type="text" class="'+(s.attr("class")||"")+' mbsc-file-input" placeholder="'+(s.attr("placeholder")||"")+'"/>').insertAfter(s),s.on("change",a._setText)}return n.addClass("mbsc-input").on("mousedown",a._mouseDown),Re.forEach(function(e){s.on(e,a._checkLabel)}),i.length&&(s.after(i),i.hasClass("mbsc-select-input")&&(a._delm=i[0],a.refresh())),a}return a(r,$e),t(r,[{key:"_setText",value:function(e){for(var t=e.target.files,a=[],s=0;s<t.length;++s)a.push(t[s].name);this._$input.val(a)}},{key:"_checkLabel",value:function(e){if(this._isFloating){var t=this._delm||this._elm;t.value||document.activeElement===t||e&&("focus"==e.type||"animationstart"==e.type&&this._$elm.is("*:-webkit-autofill"))?this._$parent.addClass("mbsc-label-floating-active"):this._$parent.removeClass("mbsc-label-floating-active")}}},{key:"_mouseDown",value:function(e){document.activeElement===this._elm&&e.target!==this._elm&&e.preventDefault()}},{key:"refresh",value:function(){this._checkLabel()}},{key:"destroy",value:function(){var t=this;i(l(r.prototype),"destroy",this).call(this),this._$parent.off("mousedown",this._mouseDown).removeClass("mbsc-ic-left mbsc-ic-right").find(".mbsc-input-ic").remove(),this._$parent.find(".mbsc-input-fill").remove(),Re.forEach(function(e){t._$elm.off(e,t._checkLabel)}),this._$elm.off("change",this._setText)}}]),r}();H("[mbsc-input]",je);var We=function(){function i(e,t){var a;u(this,i);var s=(a=m(this,l(i).call(this,e,t)))._$elm,n=s.attr("data-icon");return s.addClass("mbsc-btn mbsc-no-touch").find(".mbsc-btn-ic").remove(),n&&(s.prepend('<span class="mbsc-btn-ic mbsc-ic mbsc-ic-'+n+'"></span>'),""===s.text()&&s.addClass("mbsc-btn-icon-only")),a._$rippleElm=s,a}return a(i,$e),t(i,[{key:"getClassElm",value:function(){return this._$elm}}]),i}();H("[mbsc-button]",We);var Je=function(){function s(e,t){var a;return u(this,s),(a=m(this,l(s).call(this,e,t)))._$parent.prepend(a._$elm).addClass("mbsc-checkbox mbsc-control-w").find(".mbsc-checkbox-box").remove(),a._$elm.after('<span class="mbsc-checkbox-box"></span>'),a}return a(s,$e),s}();H("[mbsc-checkbox]",Je);var Ue=function(){function s(e,t){var a;return u(this,s),(a=m(this,l(s).call(this,e,t)))._$parent.addClass("mbsc-radio mbsc-control-w").find(".mbsc-radio-box").remove(),a._$elm.after('<span class="mbsc-radio-box"><span></span></span>'),a}return a(s,$e),s}();H("[mbsc-radio]",Ue);var qe=function(){function r(e,t){var a;u(this,r);var s=(a=m(this,l(r).call(this,e,t)))._$elm,n=a._$parent,i=n.find(".mbsc-select-input"),o=i.length?i:pa('<input tabindex="-1" class="mbsc-select-input mbsc-control" readonly>');return a._$input=o,a._delm=o[0],a._setText=a._setText.bind(c(a)),n.addClass("mbsc-select"+(a._$frame?" mbsc-select-inline":"")),s.after(o),o.after('<span class="mbsc-select-ic mbsc-ic mbsc-ic-arrow-down5"></span>'),s.on("change",a._setText),a._setText(),a}return a(r,je),t(r,[{key:"destroy",value:function(){i(l(r.prototype),"destroy",this).call(this),this._$parent.find(".mbsc-select-ic").remove(),this._$elm.off("change",this._setText)}},{key:"_setText",value:function(){var e=this._elm,t=pa(e);t.is("select")&&!t.hasClass("mbsc-comp")&&this._$input.val(-1!=e.selectedIndex?e.options[e.selectedIndex].text:""),this.refresh()}}]),r}();H("[mbsc-dropdown]",qe);var Be,Ke=["change","keydown","input","scroll"];function Ge(){clearTimeout(Be),Be=setTimeout(function(){pa("textarea.mbsc-control").each(function(){dt(this)})},100)}function dt(e){var t,a,s,n=pa(e).attr("rows")||6;e.offsetHeight&&(e.style.height="",s=e.scrollHeight-e.offsetHeight,t=e.offsetHeight+(0<s?s:0),n<(a=Math.round(t/24))?(t=24*n+(t-24*a),pa(e).addClass("mbsc-textarea-scroll")):pa(e).removeClass("mbsc-textarea-scroll"),t&&(e.style.height=t+"px"))}T&&pa(window).on("resize orientationchange",Ge);var ut=function(){function s(e,t){var a;return u(this,s),(a=m(this,l(s).call(this,e,t)))._$parent.addClass("mbsc-textarea"),Ke.forEach(function(e){a._$elm.on(e,a._handle)}),dt(e),a}return a(s,je),t(s,[{key:"destroy",value:function(){var t=this;i(l(s.prototype),"destroy",this).call(this),Ke.forEach(function(e){t._$elm.off(e,t._handle)})}},{key:"refresh",value:function(){i(l(s.prototype),"refresh",this).call(this),clearTimeout(this._debounce),dt(this._elm)}},{key:"_handle",value:function(e){switch(i(l(s.prototype),"_handle",this).call(this,e),e.type){case"change":dt(this._elm);break;case"keydown":case"input":this._onInput(e);break;case"scroll":!function(e){var t=pa(e);if(!t.hasClass("mbsc-textarea-scroll")){var a=e.scrollHeight-e.offsetHeight,s=e.offsetHeight+a;Math.round(s/24)<=(t.attr("rows")||6)&&(e.scrollTop=0,e.style.height=s+"px")}}(this._elm)}}},{key:"_onInput",value:function(){var e=this;clearTimeout(this._debounce),this._debounce=setTimeout(function(){dt(e._elm)},100)}}]),s}();H("[mbsc-textarea]",ut);var ht=function(){function r(e,t){var a,s,n;u(this,r);var i=(a=m(this,l(r).call(this,e,t)))._$elm,o=a._$parent;return o.hasClass("mbsc-segmented-item-ready")||(s=pa('<div class="mbsc-segmented mbsc-no-touch"></div>'),o.after(s),o.parent().find('input[name="'+i.attr("name")+'"]').each(function(){var e=pa(this);n=e.parent().addClass("mbsc-segmented-item mbsc-segmented-item-ready"),pa('<span class="mbsc-segmented-content">'+(e.attr("data-icon")?'<span class="mbsc-ic mbsc-ic-'+e.attr("data-icon")+'"></span>':"")+"</span>").append(n.contents()).appendTo(n),n.prepend(e),s.append(n)})),a._$rippleElm=i.next(),a}return a(r,$e),t(r,[{key:"getClassElm",value:function(){return this._$elm.closest(".mbsc-segmented")}}]),r}();H("[mbsc-segmented]",ht);function ft(t,e){var s,n,i,a,o,r,l,c,m,d,u,h,f,p,b,v,x="",g=this,T=pa(t),y=p;function _(){var e;t.disabled||(e=parseFloat(pa(this).val()),C(isNaN(e)?p:e))}function w(){return t.disabled}function M(e,t){C(p+t*d)}function C(e,t,a){y=p,void 0===t&&(t=!0),void 0===a&&(a=t),p=S(e),i.removeClass("mbsc-disabled"),t&&T.val(p),p==r?n.addClass("mbsc-disabled"):p==o&&s.addClass("mbsc-disabled"),p!==y&&a&&T.trigger("change")}function k(e,t,a){var s=T.attr(e);return void 0===s||""===s?t:a?s:+s}function S(e){return+Math.min(o,Math.max(Math.round(e/d)*d,r)).toFixed(m)}xa.call(this,t,e,!0),g.getVal=function(){var e=parseFloat(T.val());return S(e=isNaN(e)?p:e)},g.setVal=function(e,t,a){e=parseFloat(e),C(isNaN(e)?p:e,t,a)},g._init=function(){b=T.parent().hasClass("mbsc-stepper"),v=b?T.closest(".mbsc-stepper-cont"):T.parent(),h=g.settings,r=void 0===e.min?k("min",h.min):e.min,o=void 0===e.max?k("max",h.max):e.max,d=void 0===e.step?k("step",h.step):e.step,m=Math.abs(d)<1?(d+"").split(".")[1].length:0,l=void 0===e.inputStyle?k("data-input-style",h.inputStyle,!0):e.inputStyle,a=T.attr("data-val")||h.val,p=S(+t.value||0),f=Z.themes.form[h.theme],c=f&&f.addRipple?f:null,b||v.addClass("mbsc-stepper-cont mbsc-no-touch mbsc-control-w").addClass("box"==l?"mbsc-input-box":"").addClass("outline"==l?"mbsc-input-outline":"").append('<span class="mbsc-segmented mbsc-stepper"></span>').find(".mbsc-stepper").append('<span class="mbsc-segmented-item mbsc-stepper-control mbsc-stepper-minus '+(p==r?"mbsc-disabled":"")+'" data-step="-1" tabindex="0"><span class="mbsc-segmented-content"><span class="mbsc-ic mbsc-ic-minus"></span></span></span>').append('<span class="mbsc-segmented-item mbsc-stepper-control mbsc-stepper-plus '+(p==o?"mbsc-disabled":"")+'"  data-step="1" tabindex="0"><span class="mbsc-segmented-content"> <span class="mbsc-ic mbsc-ic-plus"></span></span></span>').prepend(T),x&&(v.removeClass(x),v.find(".mbsc-segmented").removeClass(x)),x="mbsc-"+h.theme+(f.baseTheme?" mbsc-"+f.baseTheme:"")+(h.rtl?" mbsc-rtl":" mbsc-ltr"),v.addClass(x),v.find(".mbsc-segmented").addClass(x),n=pa(".mbsc-stepper-minus",v),s=pa(".mbsc-stepper-plus",v),i=pa(".mbsc-stepper-control",v),b||("left"==a?(v.addClass("mbsc-stepper-val-left"),T.after('<span class="mbsc-segmented-item"><span class="mbsc-segmented-content"></span></span>')):"right"==a?(v.addClass("mbsc-stepper-val-right"),s.after('<span class="mbsc-segmented-item"><span class="mbsc-segmented-content"></span></span>')):n.after('<span class="mbsc-segmented-item"><span class="mbsc-segmented-content mbsc-stepper-val"></span></span>')),u||(T.on("change",_),u=rt(i,M,150,w,!1,c)),T.val(p).attr("data-role","stepper").attr("min",r).attr("max",o).attr("step",d).addClass("mbsc-control"),t.mbscInst=g},g._destroy=function(){T.removeClass("mbsc-control").off("change",_),u.destroy(),delete t.mbscInst},g.init()}ft.prototype={_class:"stepper",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_defaults:{min:0,max:100,step:1}},H("[mbsc-stepper]",L.Stepper=ft);function pt(t,e,a){var s,n,i,o,r=this;xa.call(this,t,e,!0),r.__init=ra,r.__destroy=ra,r._init=function(){var e;o=r.settings,s=pa(t),e=!!n,n=(n=s.parent()).hasClass("mbsc-input-wrap")?n.parent():n,r._$parent=n,i&&n.removeClass(i),i=r._css+" mbsc-progress-w mbsc-control-w "+ze(o),n.addClass(i),s.addClass("mbsc-control"),r.__init(),e||r._attachChange(),r.refresh(),t.mbscInst=r},r._destroy=function(){r.__destroy(),n.removeClass(i),s.removeClass("mbsc-control"),delete t.mbscInst},a||r.init()}function bt(a,e,t){var s,n,i,l,o,r,c,m,d,u,h,f,p,b,v,x,g,T,y,_,w,M,C,k,S,D,V,N,A,E,F,H,P,I,L=this,O=new Date;function Y(e){"mousedown"===e.type&&e.preventDefault(),!ga(e,this)||m&&!g||a.disabled||a.readOnly||(V.stopProp&&e.stopPropagation(),u=C=!(m=!0),A=ua(e,"X"),E=ua(e,"Y"),b=A,c.removeClass("mbsc-progress-anim"),n=k?pa(".mbsc-slider-handle",this):l,i&&i.removeClass("mbsc-handle-curr"),i=n.parent().addClass("mbsc-active mbsc-handle-curr"),s.addClass("mbsc-active"),x=+n.attr("data-index"),P=c[0].offsetWidth,p=c[0].getBoundingClientRect().left,"mousedown"===e.type&&(T=!0,pa(document).on("mousemove",z).on("mouseup",$)),"mouseenter"===e.type&&(g=!0,pa(document).on("mousemove",z)))}function z(e){m&&(b=ua(e,"X"),v=ua(e,"Y"),h=b-A,f=v-E,5<Math.abs(h)&&(C=!0),(C||T||g)&&50<Math.abs(O-new Date)&&(O=new Date,K(b,V.round,_&&(!g||T))),C?e.preventDefault():7<Math.abs(f)&&"touchmove"==e.type&&B())}function $(e){m&&(e.preventDefault(),k||c.addClass("mbsc-progress-anim"),g&&!T?G(I[x],x,!1,!1,!0):K(b,!0,!0),C||u||("touchend"==e.type&&da(),L._onTap(I[x])),"mouseup"==e.type&&(T=!1),"mouseleave"==e.type&&(g=!1),g||B())}function R(){m&&B()}function j(){var e=L._readValue(pa(this)),t=+pa(this).attr("data-index");e!==I[t]&&(I[t]=e,G(S[t]=e,t))}function W(e){e.stopPropagation()}function J(e){e.preventDefault()}function U(e){var t;if(!a.disabled){switch(e.keyCode){case 38:case 39:t=1;break;case 40:case 37:t=-1}t&&(e.preventDefault(),H||(x=+pa(this).attr("data-index"),G(I[x]+D*t,x,!0),H=setInterval(function(){G(I[x]+D*t,x,!0)},200)))}}function q(e){e.preventDefault(),clearInterval(H),H=null}function B(){m=!1,i.removeClass("mbsc-active"),s.removeClass("mbsc-active"),pa(document).off("mousemove",z).off("mouseup",$)}function K(e,t,a){var s=t?Math.min(Math[L._rounding||"round"](Math.max(100*(e-p)/P,0)/N/D)*D*100/(w-M+d),100):Math.max(0,Math.min(100*(e-p)/P,100));y&&(s=100-s),G(Math.round((M-d+s/N)*F)/F,x,a,s)}function G(e,t,a,s,n,i){var o=l.eq(t),r=o.parent();e=Math.min(w,Math.max(e,M)),void 0===i&&(i=a),L._update?e=L._update(e,I,t,s,k,n,r):r.css({left:y?"auto":(s||X(e,M,w))+"%",right:y?(s||X(e,M,w))+"%":"auto"}),M<e?r.removeClass("mbsc-slider-start"):(I[t]>M||n)&&r.addClass("mbsc-slider-start"),a&&(I[t]=e),a&&S[t]!=e&&(u=!0,S[t]=e,L._fillValue(e,t,i)),o.attr("aria-valuenow",e)}pt.call(this,a,e,!0),L._onTap=ra,L.___init=ra,L.___destroy=ra,L._attachChange=function(){s.on(V.changeEvent,j)},L.__init=function(){var e;l&&(e=!0,l.parent().remove()),L.___init(),r=L._$parent,c=L._$track,s=r.find("input"),V=L.settings,M=L._min,w=L._max,d=L._base||0,D=L._step,_=L._live,F=D%1!=0?100/(100*(D%1).toFixed(2)):1,N=100/(w-M+d)||100,k=1<s.length,y=V.rtl,I=[],S=[],s.each(function(e){I[e]=L._readValue(pa(this)),pa(this).attr("data-index",e)}),l=r.find(".mbsc-slider-handle"),o=r.find(k?".mbsc-slider-handle-cont":".mbsc-progress-cont"),l.on("keydown",U).on("keyup",q).on("blur",q),o.on("touchstart mousedown"+(V.hover?" mouseenter":""),Y).on("touchmove",z).on("touchend touchcancel"+(V.hover?" mouseleave":""),$).on("pointercancel",R),e||(s.on("click",W),r.on("click",J))},L.__destroy=function(){r.off("click",J),s.off(V.changeEvent,j).off("click",W),l.off("keydown",U).off("keyup",q).off("blur",q),o.off("touchstart mousedown mouseenter",Y).off("touchmove",z).off("touchend touchcancel mouseleave",$).off("pointercancel",R),L.___destroy()},L.refresh=function(){s.each(function(e){G(L._readValue(pa(this)),e,!0,!1,!0,!1)})},L.getVal=function(){return k?I.slice(0):I[0]},L.setVal=L._setVal=function(e,t,a){pa.isArray(e)||(e=[e]),pa.each(e,function(e,t){I[e]=t}),pa.each(e,function(e,t){G(t,e,!0,!1,!0,a)})},t||L.init()}function vt(e,t){var s,a,n,i,o=this;va(t=t||{},{changeEvent:"click",round:!1}),bt.call(this,e,t,!0),o._readValue=function(){return e.checked?1:0},o._fillValue=function(e,t,a){s.prop("checked",!!e),a&&s.trigger("change")},o._onTap=function(e){o._setVal(e?0:1)},o.___init=function(){n=o.settings,s=pa(e),(a=s.parent()).find(".mbsc-switch-track").remove(),a.prepend(s),s.attr("data-role","switch").after('<span class="mbsc-progress-cont mbsc-switch-track"><span class="mbsc-progress-track mbsc-progress-anim"><span class="mbsc-slider-handle-cont"><span class="mbsc-slider-handle mbsc-switch-handle" data-index="0"><span class="mbsc-switch-txt-off">'+n.offText+'</span><span class="mbsc-switch-txt-on">'+n.onText+"</span></span></span></span></span>"),i&&i.destroy(),i=new $e(e,n),o._$track=a.find(".mbsc-progress-track"),o._min=0,o._max=1,o._step=1},o.___destroy=function(){i.destroy()},o.getVal=function(){return e.checked},o.setVal=function(e,t,a){o._setVal(e?1:0,t,a)},o.init()}vt.prototype={_class:"switch",_css:"mbsc-switch",_hasTheme:!0,_hasLang:!0,_hasDef:!0,_defaults:{stopProp:!0,offText:"Off",onText:"On"}},H("[mbsc-switch]",L.Switch=vt);function xt(n,i,e){var o,r,l,c,m,d,u,h,f,p,b,v,x,t,g=this;function a(){var e=T("value",u);e!==x&&s(e)}function T(e,t,a){var s=r.attr(e);return void 0===s||""===s?t:a?s:+s}function s(e,t,a,s){e=Math.min(h,Math.max(e,u)),c.css("width",100*(e-u)/(h-u)+"%"),void 0===a&&(a=!0),void 0===s&&(s=a),e===x&&!t||g._display(e),e!==x&&(x=e,a&&r.attr("value",x),s&&r.trigger("change"))}pt.call(this,n,i,!0),g._display=function(e){t=v&&b.returnAffix?v.replace(/\{value\}/,e).replace(/\{max\}/,h):e,m&&m.html(t),o&&o.html(t)},g._attachChange=function(){r.on("change",a)},g.__init=function(){var e,t,a,s;if(b=g.settings,r=pa(n),s=!!l,l=g._$parent,u=g._min=void 0===i.min?T("min",b.min):i.min,h=g._max=void 0===i.max?T("max",b.max):i.max,f=void 0===i.inputStyle?T("data-input-style",b.inputStyle,!0):i.inputStyle,p=void 0===i.labelStyle?T("data-label-style",b.labelStyle,!0):i.labelStyle,x=T("value",u),e=r.attr("data-val")||b.val,a=(a=r.attr("data-step-labels"))?JSON.parse(a):b.stepLabels,v=r.attr("data-template")||(100!=h||b.template?b.template:"{value}%"),s?(e&&(o.remove(),l.removeClass("mbsc-progress-value-"+("right"==e?"right":"left"))),a&&pa(".mbsc-progress-step-label",d).remove()):(Le(l,null,f,p,n),Ie(r),l.find(".mbsc-input-wrap").append('<span class="mbsc-progress-cont"><span class="mbsc-progress-track mbsc-progress-anim"><span class="mbsc-progress-bar"></span></span></span>'),c=g._$progress=l.find(".mbsc-progress-bar"),d=g._$track=l.find(".mbsc-progress-track")),r.attr("min",u).attr("max",h),e&&(o=pa('<span class="mbsc-progress-value"></span>'),l.addClass("mbsc-progress-value-"+("right"==e?"right":"left")).find(".mbsc-input-wrap").append(o)),a)for(t=0;t<a.length;++t)d.append('<span class="mbsc-progress-step-label" style="'+(b.rtl?"right":"left")+": "+100*(a[t]-u)/(h-u)+'%" >'+a[t]+"</span>");m=pa(r.attr("data-target")||b.target)},g.__destroy=function(){l.removeClass("mbsc-ic-left mbsc-ic-right").find(".mbsc-progress-cont").remove(),l.find(".mbsc-input-ic").remove(),r.off("change",a)},g.refresh=function(){s(T("value",u),!0,!1)},g.getVal=function(){return x},g.setVal=function(e,t,a){s(e,!0,t,a)},e||g.init()}xt.prototype={_class:"progress",_css:"mbsc-progress",_hasTheme:!0,_hasLang:!0,_hasDef:!0,_defaults:{min:0,max:100,returnAffix:!0}},H("[mbsc-progress]",L.Progress=xt);function gt(e,t,a){var s,n,r,l,i,c,m,d,u,h,f,o,p,b=this;xt.call(this,e,t,!0);var v=b.__init,x=b.__destroy;bt.call(this,e,t,!0);var g=b.__init,T=b.__destroy;b.__init=function(){v(),g()},b.__destroy=function(){x(),T()},b._update=function(e,t,a,s,n,i,o){return d?0===a?(e=Math.min(e,t[1]),r.css({width:X(t[1],f,h)-X(e,f,h)+"%",left:u?"auto":X(e,f,h)+"%",right:u?X(e,f,h)+"%":"auto"})):(e=Math.max(e,t[0]),r.css({width:X(e,f,h)-X(t[0],f,h)+"%"})):n||!c?o.css({left:u?"auto":(s||X(e,f,h))+"%",right:u?(s||X(e,f,h))+"%":"auto"}):r.css("width",(s||X(e,f,h))+"%"),m&&l.eq(a).html(e),n||t[a]==e&&!i||b._display(e),e},b._readValue=function(e){return+e.val()},b._fillValue=function(e,t,a){s.eq(t).val(e),a&&s.eq(t).trigger("change")},b._markupReady=function(){var e,t;if(m&&n.addClass("mbsc-slider-has-tooltip"),1!=o)for(t=(h-f)/o,e=0;e<=t;++e)i.append('<span class="mbsc-slider-step" style="'+(u?"right":"left")+":"+100/t*e+'%"></span>');s.each(function(e){"range"==this.type&&pa(this).attr("min",f).attr("max",h).attr("step",o),(c?r:i).append('<span class="mbsc-slider-handle-cont'+(d&&!e?" mbsc-slider-handle-left":"")+'"><span tabindex="0" class="mbsc-slider-handle" aria-valuemin="'+f+'" aria-valuemax="'+h+'" data-index="'+e+'"></span>'+(m?'<span class="mbsc-slider-tooltip"></span>':"")+"</span>")}),l=n.find(".mbsc-slider-tooltip")},b.___init=function(){n&&(n.removeClass("mbsc-slider-has-tooltip"),1!=o&&pa(".mbsc-slider-step",i).remove()),n=b._$parent,i=b._$track,r=b._$progress,s=n.find("input"),p=b.settings,f=b._min,h=b._max,b._step=o=void 0===t.step?+s.attr("step")||p.step:t.step,b._live=S("data-live",p.live,s),m=S("data-tooltip",p.tooltip,s),c=S("data-highlight",p.highlight,s)&&s.length<3,d=c&&2==s.length,u=p.rtl,b._markupReady()},a||b.init()}gt.prototype={_class:"progress",_css:"mbsc-progress mbsc-slider",_hasTheme:!0,_hasLang:!0,_hasDef:!0,_defaults:{changeEvent:"change",stopProp:!0,min:0,max:100,step:1,live:!0,highlight:!0,round:!0,returnAffix:!0}},H("[mbsc-slider]",L.Slider=gt);function Tt(e,t,a){var o,s,r,n,i,l,c,m=this,d=pa(e);gt.call(this,e,t,!0),m._update=function(e,t,a,s,n,i){return o.css("width",X(e,0,r)+"%"),n||t[a]==e&&!i||m._display(e),e},m._markupReady=function(){var e,t="",a="";for(s=m._$track,o=m._$progress,c=m.settings,n=m._min,r=m._max,m._base=n,m._rounding=c.rtl?"floor":"ceil",i=d.attr("data-empty")||c.empty,l=d.attr("data-filled")||c.filled,e=0;e<r;++e)t+='<span class="mbsc-ic mbsc-ic-'+i+'"></span>',a+='<span class="mbsc-ic mbsc-ic-'+l+'"></span>';s.html(t),s.append(o),o.html(a),s.append('<span class="mbsc-rating-handle-cont"><span tabindex="0" class="mbsc-slider-handle" aria-valuemin="'+n+'" aria-valuemax="'+r+'" data-index="0"></span></span>')},a||m.init()}Tt.prototype={_class:"progress",_css:"mbsc-progress mbsc-rating",_hasTheme:!0,_hasLang:!0,_hasDef:!0,_defaults:{changeEvent:"change",stopProp:!0,min:1,max:5,step:1,live:!0,round:!0,hover:!0,highlight:!0,returnAffix:!0,empty:"star",filled:"star3"}},H("[mbsc-rating]",L.Rating=Tt);var yt=1,_t=function(){function l(e,t){var a,s,n,i=this;u(this,l);var o=pa(e);if(this.settings=t,this._isOpen=t.isOpen||!1,o.addClass("mbsc-collapsible "+(this._isOpen?"mbsc-collapsible-open":"")),(a=(n=o.hasClass("mbsc-card")?(s=o.find(".mbsc-card-header").eq(0).addClass("mbsc-collapsible-header"),o.find(".mbsc-card-content").eq(0).addClass("mbsc-collapsible-content")):o.hasClass("mbsc-form-group")||o.hasClass("mbsc-form-group-inset")?(s=o.find(".mbsc-form-group-title").eq(0).addClass("mbsc-collapsible-header"),o.find(".mbsc-form-group-content").eq(0).addClass("mbsc-collapsible-content")):(s=o.find(".mbsc-collapsible-header").eq(0),o.find(".mbsc-collapsible-content").eq(0)))[0])&&!a.id&&(a.id="mbsc-collapsible-"+yt++),s.length&&a){var r=pa('<span class="mbsc-collapsible-icon mbsc-ic mbsc-ic-arrow-down5"></span>');A(this,s,function(){i.collapse()}),s.attr("role","button").attr("aria-expanded",this._isOpen).attr("aria-controls",a.id).attr("tabindex","0").on("mousedown",function(e){e.preventDefault()}).on("keydown",function(e){32!==e.which&&13!=e.keyCode||(e.preventDefault(),i.collapse())}).append(r)}(e.mbscInst=this)._$header=s,this._$content=n,this._$elm=o,this._$accordionParent=o.parent("[mbsc-accordion], mbsc-accordion, .mbsc-accordion"),this.show=this.show.bind(this),this.hide=this.hide.bind(this),this.toggle=this.toggle.bind(this)}return t(l,[{key:"collapse",value:function(e){var t=this._$elm,a=this._$content;void 0===e&&(e=!this._isOpen),e&&this._isOpen||!e&&!this._isOpen||!a.length||(e?(J&&a.on("transitionend",function e(){a.off("transitionend",e).css("height","")}).css("height",a[0].scrollHeight),t.addClass("mbsc-collapsible-open")):(J&&a.css("height",getComputedStyle(a[0]).height),setTimeout(function(){a.css("height",0),t.removeClass("mbsc-collapsible-open")},50)),e&&this._$accordionParent&&this._$accordionParent.find(".mbsc-collapsible-open").each(function(){this!==t[0]&&this.mbscInst.hide()}),this._isOpen=e,this._$header.attr("aria-expanded",this._isOpen))}},{key:"show",value:function(){this.collapse(!0)}},{key:"hide",value:function(){this.collapse(!1)}},{key:"toggle",value:function(){this.collapse()}},{key:"destroy",value:function(){this._$elm.removeClass("mbsc-collapsible mbsc-collapsible-open"),this._$content.removeClass("mbsc-collapsible-content"),this._$header.removeClass("mbsc-collapsible-header").find(".mbsc-collapsible-icon").remove()}}]),l}();L.CollapsibleBase=_t;var wt=0;function Mt(e,s,n,t){pa("input,select,textarea,progress,button",e).each(function(){var e=this,t=pa(e),a=ha(t);if("false"!=t.attr("data-enhance"))if(t.hasClass("mbsc-control"))e.mbscInst&&e.mbscInst.option({theme:n.theme,lang:n.lang,rtl:n.rtl,onText:n.onText,offText:n.offText,stopProp:n.stopProp});else switch(e.id||(e.id="mbsc-form-control-"+ ++wt),a){case"button":case"submit":s[e.id]=new We(e,{theme:n.theme,rtl:n.rtl,tap:n.tap});break;case"switch":s[e.id]=new vt(e,{theme:n.theme,lang:n.lang,rtl:n.rtl,tap:n.tap,onText:n.onText,offText:n.offText,stopProp:n.stopProp});break;case"checkbox":s[e.id]=new Je(e,{tap:n.tap,theme:n.theme,rtl:n.rtl});break;case"range":pa(e).parent().hasClass("mbsc-slider")||(s[e.id]=new gt(e,{theme:n.theme,lang:n.lang,rtl:n.rtl,stopProp:n.stopProp,labelStyle:n.labelStyle}));break;case"rating":s[e.id]=new Tt(e,{theme:n.theme,lang:n.lang,rtl:n.rtl,stopProp:n.stopProp});break;case"progress":s[e.id]=new xt(e,{theme:n.theme,lang:n.lang,rtl:n.rtl,labelStyle:n.labelStyle});break;case"radio":s[e.id]=new Ue(e,{tap:n.tap,theme:n.theme,rtl:n.rtl});break;case"select":case"select-one":case"select-multiple":s[e.id]=new qe(e,{tap:n.tap,inputStyle:n.inputStyle,labelStyle:n.labelStyle,theme:n.theme,rtl:n.rtl});break;case"textarea":s[e.id]=new ut(e,{tap:n.tap,inputStyle:n.inputStyle,labelStyle:n.labelStyle,theme:n.theme,rtl:n.rtl});break;case"segmented":s[e.id]=new ht(e,{theme:n.theme,rtl:n.rtl,tap:n.tap,inputStyle:n.inputStyle});break;case"stepper":s[e.id]=new ft(e,{theme:n.theme,rtl:n.rtl});break;case"hidden":return;default:s[e.id]=new je(e,{tap:n.tap,inputStyle:n.inputStyle,labelStyle:n.labelStyle,theme:n.theme,rtl:n.rtl})}}),pa("[data-collapsible]:not(.mbsc-collapsible)",e).each(function(){var e=this,t=pa(e).attr("data-open");e.id||(e.id="mbsc-form-control-"+ ++wt),s[e.id]=new _t(e,{isOpen:void 0!==t&&"false"!=t}),ba[e.id]=s[e.id]}),t||Ge()}
/* eslint-disable no-unused-vars */function Ct(a,e){var s,n,i="",o=pa(a),t={},r=this;function l(){o.removeClass("mbsc-no-touch")}xa.call(this,a,e,!0),r.refresh=function(e){Mt(o,t,s,e)},r._init=function(){var e=void 0!==s.collapsible||void 0!==o.attr("data-collapsible");if(o.hasClass("mbsc-card")||o.on("touchstart",l).show(),i&&o.removeClass(i),i="mbsc-card mbsc-form mbsc-no-touch mbsc-"+s.theme+(kt?" mbsc-form-hb":"")+(s.baseTheme?" mbsc-"+s.baseTheme:"")+(s.rtl?" mbsc-rtl":" mbsc-ltr"),o.addClass(i).removeClass("mbsc-cloak"),e&&!n){var t=o.attr("data-open");n=new _t(a,{isOpen:void 0!==t&&"false"!=t||!0===s.collapsible})}r.refresh()},r._destroy=function(){for(var e in o.removeClass(i).off("touchstart",l),t)t[e].destroy();n&&n.destroy()},r.toggle=function(){n&&n.toggle()},r.hide=function(){n&&n.hide()},r.show=function(){n&&n.show()},s=r.settings,r.init()}var kt="ios"==p&&7<v;function St(e){var a=[Math.round(e.r).toString(16),Math.round(e.g).toString(16),Math.round(e.b).toString(16)];return pa.each(a,function(e,t){1==t.length&&(a[e]="0"+t)}),"#"+a.join("")}function Dt(e){return{r:(e=parseInt(-1<e.indexOf("#")?e.substring(1):e,16))>>16,g:(65280&e)>>8,b:255&e,toString:function(){return"rgb("+this.r+","+this.g+","+this.b+")"}}}function Vt(e){var t,a,s,n=e.h,i=255*e.s/100,o=255*e.v/100;if(0==i)t=a=s=o;else{var r=(255-i)*o/255,l=n%60*(o-r)/60;360==n&&(n=0),n<60?(t=o,a=(s=r)+l):n<120?(s=r,t=(a=o)-l):n<180?(a=o,s=(t=r)+l):n<240?(t=r,a=(s=o)-l):n<300?(s=o,t=(a=r)+l):n<360?(a=r,s=(t=o)-l):t=a=s=0}return{r:t,g:a,b:s,toString:function(){return"rgb("+this.r+","+this.g+","+this.b+")"}}}function Nt(e){var t,a,s=0,n=Math.min(e.r,e.g,e.b),i=Math.max(e.r,e.g,e.b),o=i-n;return s=(t=(a=i)?255*o/i:0)?e.r==i?(e.g-e.b)/o:e.g==i?2+(e.b-e.r)/o:4+(e.r-e.g)/o:-1,(s*=60)<0&&(s+=360),{h:s,s:t*=100/255,v:a*=100/255,toString:function(){return"hsv("+Math.round(this.h)+","+Math.round(this.s)+"%,"+Math.round(this.v)+"%)"}}}function At(e){var t,a,s=e.r/255,n=e.g/255,i=e.b/255,o=Math.max(s,n,i),r=Math.min(s,n,i),l=(o+r)/2;if(o==r)t=a=0;else{var c=o-r;switch(a=.5<l?c/(2-o-r):c/(o+r),o){case s:t=(n-i)/c+(n<i?6:0);break;case n:t=(i-s)/c+2;break;case i:t=(s-n)/c+4}t/=6}return{h:Math.round(360*t),s:Math.round(100*a),l:Math.round(100*l),toString:function(){return"hsl("+this.h+","+this.s+"%,"+this.l+"%)"}}}function Et(e){return At(Dt(e))}function Ft(e){return St(function(e){var t,a,s,n,i,o,r=e.h,l=e.s,c=e.l;return isFinite(r)||(r=0),isFinite(l)||(l=0),isFinite(c)||(c=0),(r/=60)<0&&(r=6- -r%6),r%=6,l=Math.max(0,Math.min(1,l/100)),c=Math.max(0,Math.min(1,c/100)),o=(i=(1-Math.abs(2*c-1))*l)*(1-Math.abs(r%2-1)),s=r<1?(t=i,a=o,0):r<2?(t=o,a=i,0):r<3?(t=0,a=i,o):r<4?(t=0,a=o,i):r<5?(t=o,a=0,i):(t=i,a=0,o),n=c-i/2,{r:Math.round(255*(t+n)),g:Math.round(255*(a+n)),b:Math.round(255*(s+n)),toString:function(){return"rgb("+this.r+","+this.g+","+this.b+")"}}}(e))}function Ht(e){return St(Vt(e))}function Pt(e){return Nt(Dt(e))}Ct.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_class:"card",_defaults:{tap:W,stopProp:!0,rtl:!1}},H("[mbsc-card]",L.Card=Ct,!0),f("card",Ct,!1);function It(a,e,t){var c,s,m,n,d,u,i,o,r,l,h,f,p,b,v,x,g,T,y,_,w,M,C,k,S,D=this,V=pa(a),N=0,A={},E={};function F(e,t,a){if(!a){D._value=D._hasValue?D._tempValue.slice(0):null;for(var s=0;s<m.length;++s)m[s].tempChangedColor&&D._value&&-1!=D._value.indexOf(m[s].tempChangedColor)&&(m[s].changedColor=m[s].tempChangedColor),delete m[s].tempChangedColor}e&&(D._isInput&&V.val(D._hasValue?D._tempValue:""),n("onFill",{valueText:D._hasValue?D._tempValue:"",change:t}),t&&(A=va(!0,{},E),D._preventChange=!0,V.trigger("change")),Y(D._value,!0))}function H(e,t){return'<div class="mbsc-color-input-item" data-color="'+(void 0!==(t=void 0!==t?t:I(e))?t:e)+'" style="background: '+e+';">'+(T?"":'<div class="mbsc-color-input-item-close mbsc-ic mbsc-ic-material-close"></div>')+"</div>"}function P(e){f[0].style.background=e?_a+"linear-gradient(left, "+(c.rtl?"#000000":"#FFFFFF")+" 0%, "+e+" 50%, "+(c.rtl?"#FFFFFF":"#000000")+" 100%)":""}function I(e){if(Object.keys(E).length&&!isNaN(e))return e;for(var t in m)if(e==m[t].color||e==m[t].changedColor)return t}function L(e,t){var a,s=e.match(/\d+/gim);switch(!0){case-1<e.indexOf("rgb"):a=St({r:s[0],g:s[1],b:s[2]});break;case-1<e.indexOf("hsl"):a=Ft({h:s[0],s:s[1],l:s[2]});break;case-1<e.indexOf("hsv"):a=Ht({h:s[0],s:s[1],v:s[2]});break;case-1<e.indexOf("#"):a=e}return function(e,t){switch(t){case"rgb":return Dt(e);case"hsl":return Et(e);case"hsv":return Pt(e);default:return e}}(a,t||c.format)}function O(e,t){pa(".mbsc-color-active",t).removeClass("mbsc-color-active"),p&&(e.parent().addClass("mbsc-color-active"),h&&e&&void 0!==N&&C.eq(N).parent().addClass("mbsc-color-active"))}function Y(e,t){var a,s,n=[],i=0,o=pa.map(m,function(e){return e.changedColor||e.color});if(T){if(e=pa.isArray(e)?e[0]:e,-1<(s=o.indexOf(e))&&n.push(s),e&&!n.length&&p){var r=+pa(".mbsc-color-input-item",w).attr("data-color");isNaN(r)?r=void 0:n.push(r),x=r}}else if(e)if(h&&p)for(var l in A)void 0!==A[l].colorIndex&&n.push(+A[l].colorIndex);else for(a=0;a<e.length;++a)-1<(s=o.indexOf(e[a]))&&(n.push(s),o[s]="temp"+a);for(a=0;a<n.length;++a)m[n[a]]&&z(!0,n[a],i++,m[n[a]].changedColor||m[n[a]].color,!0);for(a=0;a<m.length;++a)-1==n.indexOf(a)&&z(!1,a,void 0,m[a].changedColor||m[a].color,!1);if(h)for(a=i;a<c.select;++a)E[a]={},C&&C.eq(a).addClass("mbsc-color-preview-item-empty").css({background:"transparent"});A=va(!0,{},E),!1!==t&&function(){if(g){var e,t="";if(w.empty(),D._hasValue){if(T)t+=H(D._value,x);else for(e=0;e<D._value.length;++e)t+=H(D._value[e],Object.keys(E).length&&E[e].colorIndex?E[e].colorIndex:I(D._value[e]));w.append(t),D.tap(pa(".mbsc-color-input-item",w),function(e){if(pa(e.target).hasClass("mbsc-color-input-item-close")){var t=pa(this).index();e.stopPropagation(),e.preventDefault(),void 0===x&&(x=pa(e.target).parent().attr("data-color")),h&&m[x]&&(N=m[x].previewInd,C.eq(N).parent().removeClass("mbsc-color-active"),A[t]={},E[t]={}),D._value.splice(t,1),D.setVal(D._value,!0,!0)}else p&&"inline"!==c.display&&(x=pa(e.target).attr("data-color"),isNaN(x)&&(x=I(x)),x&&m[x]&&(m[x].selected=!0,N=m[x].previewInd,setTimeout(function(){d.scroll(M.eq(x),400),h&&u.scroll(C.eq(N),400)},200)))})}}}()}function z(e,t,a,s,n,i){if(h&&n&&(E[a].colorIndex=e?t:void 0,E[a].color=e?s:void 0,C)){var o=C.eq(a);o.removeClass("mbsc-color-preview-item-empty").css({background:e?s:"transparent"}),e||o.addClass("mbsc-color-preview-item-empty").parent().removeClass("mbsc-color-active")}i&&(e?D._tempValue.splice(a,0,s):D._tempValue.splice(D._tempValue.indexOf(s),1)),M&&(e?M.eq(t).addClass("mbsc-color-selected"):M.eq(t).removeClass("mbsc-color-selected").parent().removeClass("mbsc-color-active")),m[t].previewInd=e?a:void 0,m[t].selected=e}function $(e,t){void 0!==e&&(T||m[e]&&m[e].selected)?m[x=e]&&(o=m[e].changedColor||m[e].color,k=M.eq(e),p&&(O(M.eq(e),t||""),(r=L(m[e].color,"hsl")).l=L(o,"hsl").l,P(m[e].color),v.setVal(100-r.l,!1,!1))):p&&P()}function R(e,t){var a=pa(e.target).index();x=E[a].colorIndex,k=M.eq(x),N=a,$(x,t),d.scroll(k,250),n("onPreviewItemTap",{target:e.target,value:E[a].color,index:a})}function j(e,t){var a=!1,s=pa(".mbsc-color-selected",t);if((k=pa(e.target)).hasClass("mbsc-color-clear-item"))return o="",void D.clear();(T||y>+s.length||k.hasClass("mbsc-color-selected"))&&(x=k.attr("data-index"),h&&(N=void 0!==m[x].previewInd?m[x].previewInd:function(){var e;for(e=0;e<c.select;++e)if(void 0===E[e].colorIndex)return e}(),a=p&&k.hasClass("mbsc-color-selected")&&!k.parent().hasClass("mbsc-color-active"),6<C.length&&u.scroll(C.eq(N))),o=m[x].changedColor||m[x].color,T?(s.removeClass("mbsc-color-selected"),(D._tempValue=o)&&k.toggleClass("mbsc-color-selected"),O(k,t)):(O(k,t),a||z(!m[x].selected,x,N,o,!0,!0)),$(x,t),D.live&&(D._fillValue(),n("onSet",{value:D._value})),n("onItemTap",{target:e.target,value:o,selected:m[x].selected,index:x}),D._updateHeader())}fe.call(this,a,e,!0),D.setVal=D._setVal=function(e,t,a,s){D._hasValue=null!=e,D._tempValue=T?pa.isArray(e)?e[0]:e:pa.isArray(e)?e:e?[e]:[],F(t,void 0===a?t:a,s)},D.getVal=D._getVal=function(e){return D._hasValue||e?_?function(){var e,t=[];for(e=0;e<m.length;++e)m[e].selected&&t.push(m[e]);return t}():D[e?"_tempValue":"_value"]:null},D._readValue=function(){var e=V.val()||"";D._hasValue=!1,0!==e.length&&""!==e&&(D._hasValue=!0),D._hasValue?(D._tempValue=T?e:"hex"==c.format?e.split(","):e.match(/[a-z]{3}\((\d+\.?\d{0,}?),\s*([\d.]+)%{0,},\s*([\d.]+)%{0,}\)/gim),F(!0)):D._tempValue=[],Y(D._tempValue,D._hasValue)},D._fillValue=function(){F(D._hasValue=!0,!0)},D._generateContent=function(){var e,t,a,s=i?1:0;for(b=l?Math.ceil((m.length+s)/c.rows):c.rows,t='<div class="mbsc-color-scroll-cont mbsc-w-p '+(l?"":"mbsc-color-vertical")+'"><div class="mbsc-color-cont">'+(l?'<div class="mbsc-color-row">':""),e=0;e<m.length;++e)a=m[e].changedColor||m[e].color,i&&0===e&&(t+='<div class="mbsc-color-item-c"><div tabindex="0" class="mbsc-color-clear-item mbsc-btn-e mbsc-color-selected"><div class="mbsc-color-clear-cross"></div></div></div>'),0!==e&&(e+s)%b==0&&(t+=l?'</div><div class="mbsc-color-row">':""),t+='<div class="mbsc-color-item-c"><div tabindex="0" data-index="'+e+'" class="mbsc-color-item mbsc-btn-e mbsc-ic mbsc-ic-material-check mbsc-color-btn-e '+(m[e].selected?"mbsc-color-selected":"")+'"  style="background:'+a+'"></div></div>';if(t+="</div></div>"+(l?"</div>":""),p&&(t+='<div class="mbsc-color-slider-cont"><input class="mbsc-color-slider" type="range" data-highlight="false" value="50" min="0" max="100"/></div>'),h){for(var n in t+='<div class="mbsc-color-preview-cont"><div class="mbsc-color-refine-preview">',A)t+='<div class="mbsc-color-preview-item-c mbsc-btn-e mbsc-color-btn-e" tabindex="0"><div class="mbsc-color-preview-item '+(A[n].color?"":"mbsc-color-preview-item-empty")+'" style="background: '+(A[n].color||"initial")+';"></div></div>';t+="</div></div>"}return t},D._position=function(e){var t,a;l||(t=e.find(".mbsc-color-cont"),a=Math.ceil(t.find(".mbsc-color-item-c")[0].offsetWidth),t.width(Math.min(Math.floor(e.find(".mbsc-fr-c").width()/a),Math.round(m.length/c.rows))*a+1)),d&&d.refresh(),u&&u.refresh()},D._markupInserted=function(t){l||t.find(".mbsc-color-scroll-cont").css("max-height",t.find(".mbsc-color-item-c")[0].offsetHeight*c.rows),d=new lt(t.find(".mbsc-color-scroll-cont")[0],{axis:l?"X":"Y",rtl:c.rtl,elastic:60,stopProp:!1,mousewheel:c.mousewheel,onBtnTap:function(e){j(e,t)}})},D._attachEvents=function(t){var e;M=pa(".mbsc-color-item",t),t.on("keydown",".mbsc-color-btn-e",function(e){e.stopPropagation(),32==e.keyCode&&(e.target.classList.contains("mbsc-color-item")?j(e,t):R(e,t))}),h&&(C=pa(".mbsc-color-preview-item",t)),p&&(t.addClass("mbsc-color-refine"),S=pa(".mbsc-color-slider",t),v=new gt(S[0],{theme:c.theme,rtl:c.rtl}),f=t.find(".mbsc-progress-track"),x&&D._value&&$(x,t),S.on("change",function(){void 0!==x&&(T||m[x]&&m[x].selected)&&(r.l=100-this.value,e=L(r.toString()).toString(),T?D._tempValue=e:D._tempValue[void 0!==N?N:D._tempValue.length]=e,m[x].tempChangedColor=e,M.eq(x).css("background",e),h&&(E[N].color=e,C.eq(N).removeClass("mbsc-color-preview-item-empty").css({background:e})),D.live&&ca(D._fillValue()))})),h&&(u=new lt(t.find(".mbsc-color-preview-cont")[0],{axis:"X",rtl:c.rtl,stopProp:!1,mousewheel:c.mousewheel,onBtnTap:function(e){R(e,t)}})),D._updateHeader()},D._markupRemove=function(){d&&d.destroy(),v&&v.destroy(),u&&u.destroy()},D.__processSettings=function(){var e,t;if(c=D.settings,n=D.trigger,l="horizontal"==c.navigation,D._value=[],D._tempValue=[],T="single"==c.select,i=void 0!==c.clear?c.clear:T,!(t=c.data||[]).length)switch(c.format){case"rgb":t=["rgb(255,235,60)","rgb(255,153,0)","rgb(244,68,55)","rgb(234,30,99)","rgb(156,38,176)","rgb(104,58,183)","rgb(63,81,181)","rgb(33,150,243)","rgb(0,151,136)","rgb(75,175,79)","rgb(126,93,78)","rgb(158,158,158)"],i&&t.splice(10,0,"rgb(83, 71, 65)");break;case"hsl":t=["hsl(54,100%,62%)","hsl(36,100%,50%)","hsl(4,90%,59%)","hsl(340,83%,52%)","hsl(291,64%,42%)","hsl(262,52%,47%)","hsl(231,48%,48%)","hsl(207,90%,54%)","hsl(174,100%,30%)","hsl(122,40%,49%)","hsl(19,24%,40%)","hsl(0,0%,62%)"],i&&t.splice(10,0,"hsl(20, 12%, 29%)");break;default:t=["#ffeb3c","#ff9900","#f44437","#ea1e63","#9c26b0","#683ab7","#3f51b5","#2196f3","#009788","#4baf4f","#7e5d4e","#9e9e9e"],i&&t.splice(10,0,"#534741")}if(p="refine"==c.mode,h=!isNaN(c.select),y=isNaN(c.select)?T?2:t.length:c.select,_=pa.isPlainObject(t[0]),h&&!Object.keys(A).length)for(e=0;e<c.select;++e)A[e]={},E[e]={};for(m=t.slice(0),e=0;e<m.length;++e)pa.isPlainObject(t[e])?m[e].color=t[e].color:(t[e]=t[e].toLowerCase(),m[e]={key:e,name:t[e],color:t[e]});s=c.defaultValue||m[0].color,r=L(o=s,"hsl"),(g=c.enhance&&V.is("input"))&&(V.hasClass("mbsc-color-input-hdn")?w=V.prev():((w=pa("<div "+(a.placeholder?'data-placeholder="'+a.placeholder+'"':"")+' class="mbsc-control mbsc-color-input '+(c.inputClass||"")+'" readonly ></div>')).insertBefore(V),V.addClass("mbsc-color-input-hdn").attr("tabindex",-1)),c.anchor=w,D.attachShow(w))},D.__destroy=function(){g&&(V.removeClass("mbsc-color-input-hdn"),w.remove())},D._checkSize=!0,t||D.init()}It.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_class:"color",_defaults:va({},fe.prototype._defaults,{headerText:!1,validate:ra,parseValue:ra,enhance:!0,rows:2,select:"single",format:"hex",navigation:"horizontal",compClass:"mbsc-color"})},L.Color=It,Z.themes.color=Z.themes.frame,f("color",It,!(o.color={hsv2hex:Ht,hsv2rgb:Vt,rgb2hsv:Nt,rgb2hex:St,rgb2hsl:At,hex2rgb:Dt,hex2hsv:Pt,hex2hsl:Et})),Me.date=ct,Me.time=ct,Me.datetime=ct,f("date",De),f("time",De),f("datetime",De);var Lt=function(e,t,a){function s(e){pa(".mbsc-fr-c",e).hasClass("mbsc-wdg-c")||(pa(".mbsc-fr-c",e).addClass("mbsc-wdg-c").append(o.show()),pa(".mbsc-w-p",e).length||pa(".mbsc-fr-c",e).addClass("mbsc-w-p"))}var n,i,o=pa(e),r=this;fe.call(this,e,t,!0),r._generateContent=function(){return""},r._markupReady=function(e){"inline"!=n.display&&s(e)},r._markupInserted=function(e){"inline"==n.display&&s(e),e.trigger("mbsc-enhance",[{theme:n.theme,lang:n.lang}])},r._markupRemove=function(){o.hide(),i&&i.parent().length&&i.after(o)},r.__processSettings=function(){n=r.settings,r.buttons.ok={text:n.okText,icon:n.okIcon,handler:"set"},n.buttons=n.buttons||("inline"==n.display?[]:["ok"]),!i&&o.parent().length&&(i=pa(document.createComment("popup")),o.before(i)),o.hide()},a||r.init()};Lt.prototype={_hasDef:!0,_hasTheme:!0,_hasContent:!0,_hasLang:!0,_responsive:!0,_class:"popup",_defaults:va({},fe.prototype._defaults,{compClass:"mbsc-wdg",okText:"OK",headerText:!1})},L.Popup=Lt;var Ot=L.Widget=Lt;Z.themes.popup=Z.themes.frame;var Yt=0;function zt(e,t,a){"jsonp"==a?function(e,t){var a=document.createElement("script"),s="mbscjsonp"+ ++Yt;window[s]=function(e){a.parentNode.removeChild(a),delete window[s],e&&t(e)},a.src=e+(0<=e.indexOf("?")?"&":"?")+"callback="+s,document.body.appendChild(a)}(e,t):function(e,t){var a=new XMLHttpRequest;a.open("GET",e,!0),a.onload=function(){200<=this.status&&this.status<400&&t(JSON.parse(this.response))},a.onerror=function(){},a.send()}(e,t)}o.getJson=zt;var $t={view:{calendar:{type:"month",popover:!0}},allDayText:"All-day",labelsShort:["Yrs","Mths","Days","Hrs","Mins","Secs"],eventText:"event",eventsText:"events",noEventsText:"No events"},Rt={yearChange:!1,weekDays:"short"};Me.eventcalendar=function(y,e){function s(e,t,a){var s,n,i,o,r=0,l=[],c="",m=[];for(a=a||y._prepareObj(K,e,t),s=et(e);s<=t;s.setDate(s.getDate()+1))(o=a[et(s)])&&o.length&&m.push({d:new Date(s),list:_(o)});if(0<m.length)for(n=0;n<m.length;n++){for(c+='<div><div class="mbsc-lv-gr-title mbsc-event-day" data-full="'+tt((o=m[n]).d)+'">'+re(U.dateFormat,o.d,U)+"</div>",i=0;i<o.list.length;i++){var d=o.list[i],u=d.start?at(d.start):null,h=d.end?at(d.end):null,f=d.color,p=it.test(d.d)||nt.test(d.d),b=u&&h&&!st(u,h),v=!b||st(u,o.d),x=!b||st(h,o.d),g=d.d?p?d.d:at(d.d):u,T=d.allDay||p||b&&!v&&!x;l.push({d:o.d,e:d}),c+='<div class="mbsc-lv-item mbsc-lv-item-actionable" data-index="'+r+'"><div class="mbsc-event-time">'+(T?U.allDayText:v&&g&&g.getTime?re(U.timeFormat,g):b&&x?U.toText:"")+(!T&&x&&h&&h.getTime?"<br/>"+re(U.timeFormat,h):"")+'</div><div class="mbsc-event-color"'+(f?' style="background:'+f+';"':"")+'></div><div class="mbsc-event-txt">'+d.text+"</div></div>",r++}c+="</div>"}else c+='<div class="mbsc-lv-gr-title mbsc-event-empty"><div class="mbsc-empty"><h3>'+U.noEventsText+"</h3></div></div>";q++,w.html('<div class="mbsc-lv mbsc-lv-v">'+c+"</div>").scrollTop(0),setTimeout(function(){q--},150),y.tap(pa(".mbsc-lv-item",w),function(e){var t=l[pa(this).attr("data-index")];X("onEventSelect",{domEvent:e,event:t.e,date:t.d})})}function n(){if(V){var e=et(V.d);!function(t,f,e){if(t){var a,s,n,i,p='<div class="mbsc-cal-event-list">';a=pa('<div class="mbsc-cal-events '+(U.eventBubbleClass||"")+'"><div class="mbsc-cal-events-i"><div class="mbsc-cal-events-sc"></div><div class="mbsc-sc-bar-c"><div class="mbsc-sc-bar"></div></div></div></div>'),s=pa(".mbsc-cal-events-i",a),n=pa(".mbsc-cal-events-sc",a),y.tap(s,function(){i.scrolled||o()}),N=new Lt(a[0],{display:"bubble",theme:U.theme,lang:U.lang,context:U.context,buttons:[],anchor:e,showOverlay:!1,cssClass:"mbsc-no-padding mbsc-cal-events-popup",onShow:function(){i=new lt(s[0],{scrollbar:pa(".mbsc-sc-bar-c",a),stopProp:!1})},onClose:function(e,t){t.destroy(),i.destroy()}}),b=e,t=_(t),pa.each(t,function(e,t){var a=t.start?at(t.start):null,s=t.end?at(t.end):null,n=it.test(t.d)||nt.test(t.d),i=t.d?n?t.d:at(t.d):a,o=a&&s&&!st(a,s),r=!o||st(a,f),l=!o||st(s,f),c=t.allDay||n||o&&!r&&!l,m=t.color,d="",u="",h=pa("<div>"+t.text+"</div>").text();i.getTime&&(d=re((o?"MM d yy ":"")+U.timeFormat,i)),s&&(u=re((o?"MM d yy ":"")+U.timeFormat,s)),p+='<div role="button" title="'+h+'" aria-label="'+h+(d?", "+U.fromText+": "+d:"")+(u?", "+U.toText+": "+u:"")+'" class="mbsc-cal-event mbsc-lv-item mbsc-lv-item-actionable"><div class="mbsc-cal-event-color" style="'+(m?"background:"+m+";":"")+'"></div><div class="mbsc-cal-event-text"><div class="mbsc-cal-event-time">'+(c?U.allDayText:r&&i.getTime?re(U.timeFormat,i):"")+"</div>"+t.text+"</div>"+(a&&s&&!t.allDay?'<div class="mbsc-cal-event-dur">'+U.formatDuration(a,s,t)+"</div>":"")+"</div>"}),p+="</div>",n.html(p),N.show(),X("onEventBubbleShow",{target:b,eventList:a[0]}),y.tap(pa(".mbsc-cal-event",n),function(e){i.scrolled||X("onEventSelect",{domEvent:e,event:t[pa(this).index()],date:f})}),g=!0}}(V.events||x[e],e,V.cell||pa('.mbsc-cal-slide-a .mbsc-cal-day[data-full="'+tt(e)+'"]',y._markup)[0]),V=null}}function _(e){return e.slice(0).sort(function(e,t){var a=e.start?at(e.start):null,s=t.start?at(t.start):null,n=e.end?at(e.end):null,i=t.end?at(t.end):null,o=it.test(e.d)||nt.test(e.d),r=it.test(t.d)||nt.test(t.d),l=e.d?o?e.d:at(e.d):a,c=t.d?r?t.d:at(t.d):s,m=l.getTime?a&&n&&a.toDateString()!==n.toDateString()?1:e.allDay?2:l.getTime():0,d=c.getTime?s&&i&&s.toDateString()!==i.toDateString()?1:t.allDay?2:c.getTime():0;return m==d?e.text>t.text?1:-1:m-d})}function a(){var e,t,a;q||pa(".mbsc-event-day",this).each(function(){if(0<=(t=this.offsetTop-v.scrollTop)&&t<35)return a=pa(this).attr("data-full").split("-"),st(e=Ze(a[0],a[1]-1,a[2]),h)||(A=!0,y.setVal(e)),!1})}function o(){N&&g&&N.hide(),b=null,g=!1}function i(e){0==pa(e.target).closest(".mbsc-cal-day").length&&o()}function t(){o(),y.redraw()}function r(e){var t=U.getYear(e),a=U.getMonth(e),s=U.getDay(e);if(f=e,"day"==k)p=U.getDate(t,a,s+S-1);else if("week"==k){var n,i=f.getDay();n=s+U.firstDay-(0<U.firstDay-i?7:0)-i,f=U.getDate(t,a,n),p=U.getDate(t,a,n+7*S-1)}else"month"==k?(f=U.getDate(t,a,1),p=U.getDate(t,a+S,0)):"year"==k&&(f=U.getDate(t,0,1),p=U.getDate(t+S,0,0))}function l(e,t){if(I&&!A){var a=pa('.mbsc-event-day[data-full="'+tt(e)+'"]',w);a.length&&(q++,Q(v,a.parent()[0].offsetTop,t,function(){setTimeout(function(){q--},150)}))}}function c(e,t){e&&X("onPageChange",{firstDay:f,lastDay:p}),t||X("onPageLoading",{firstDay:f,lastDay:p}),X("onPageLoaded",{firstDay:f,lastDay:p})}var m,d,w,u,h,f,p,b,v,x,g,T,M,C,k,S,D,V,N,A,E,F,H,P,I,L,O,Y,z,$,R,j,W=this,J=va({},y.settings),U=va(y.settings,$t,J,Rt,e),q=0,B=0,K=va(!0,[],U.data),G=!0,X=y.trigger;return U.data=K,pa.each(K,function(e,t){void 0===t._id&&(t._id=B++)}),Y=U.view,z=Y.calendar,$=Y.eventList,R=U.months,j=U.weeks,C=z?("week"==z.type?j=z.size||1:z.size&&(R=z.size),!1):!(j=0),$&&(k=$.type,S=$.size||1),D=z&&z.labels,P=$&&$.scrollable,I=Y.eventList,L=void 0===U.eventBubble?z&&z.popover:U.eventBubble,U.weeks=j,U.months=R,m=Ve.call(this,y),y._onSelectShow=function(){o()},y._onGenMonth=function(e,t){x=y._prepareObj(K,e,t),y._labels=D?x:null},y._onRefresh=function(e){E=!0,H=F=null,C&&c(!1,e)},y._onSetDate=function(e,t){h=e,C?A||(r(e),c(!0)):t||T||(I&&"day"==k&&s(e,e,x),!L&&!O||M||n(),l(e)),M=O=A=!1},y._getDayProps=function(e){var t=x[e],a={events:t};return U.marked||U.labels||D||(t?(a.background=t[0]&&t[0].background,a.marked=t,a.markup=U.showEventCount?'<div class="mbsc-cal-txt">'+t.length+" "+(1<t.length?U.eventsText:U.eventText)+"</div>":'<div class="mbsc-cal-marks"><div class="mbsc-cal-mark"></div></div>'):a.markup=U.showEventCount?'<div class="mbsc-cal-txt-ph"></div>':""),a},y.addEvent=function(e){var a=[];return e=va(!0,[],pa.isArray(e)?e:[e]),pa.each(e,function(e,t){void 0===t._id&&(t._id=B++),K.push(t),a.push(t._id)}),t(),a},y.updateEvent=function(a){pa.each(K,function(e,t){if(t._id===a._id)return K.splice(e,1,a),!1}),t()},y.removeEvent=function(e){e=pa.isArray(e)?e:[e],pa.each(e,function(e,a){pa.each(K,function(e,t){if(t._id===a)return K.splice(e,1),!1})}),t()},y.getEvents=function(e){var t;return e?(e.setHours(0,0,0,0),(t=y._prepareObj(K,e,e))[e]?_(t[e]):[]):va(!0,[],K)},y.setEvents=function(e){var a=[];return U.data=K=va(!0,[],e),pa.each(K,function(e,t){void 0===t._id&&(t._id=B++),a.push(t._id)}),t(),a},y.navigate=function(e,t,a){V=a?{d:e}:null,y.setVal(e,!0,!0,!1,t?200:0)},va({},m,{multiLabel:D,headerText:!1,buttons:"inline"!==U.display?["close"]:U.buttons,compClass:"mbsc-ev-cal mbsc-calendar mbsc-dt mbsc-sc",formatDuration:function(e,t){var a=U.labelsShort,s=t-e,n=Math.abs(s)/1e3,i=n/60,o=i/60,r=o/24,l=r/365;return n<45&&Math.round(n)+" "+a[5].toLowerCase()||i<45&&Math.round(i)+" "+a[4].toLowerCase()||o<24&&Math.round(o)+" "+a[3].toLowerCase()||r<30&&Math.round(r)+" "+a[2].toLowerCase()||r<365&&Math.round(r/30)+" "+a[1].toLowerCase()||Math.round(l)+" "+a[0].toLowerCase()},onMarkupReady:function(e,t){d=pa(e.target),h=t.getDate(!0),I&&((w=pa('<div class="mbsc-lv-cont mbsc-lv-'+U.theme+(U.baseTheme?" mbsc-lv-"+U.baseTheme:"")+(P?" mbsc-event-list-h":"")+' mbsc-event-list"></div>').appendTo(pa(".mbsc-fr-w",d))).on("scroll",ca(a)),v=w[0]),m.onMarkupReady.call(this,e),u=pa(".mbsc-cal-month",d),g=!1,r(h),I&&C&&(c(),rt(pa(".mbsc-cal-btn",d),function(e,t){var a=U.getYear(f),s=U.getMonth(f),n=U.getDay(f);"day"==k?(f=U.getDate(a,s,n+t*S),p=U.getDate(a,s,n+(t+1)*S-1)):"week"==k?(f=U.getDate(a,s,n+t*S*7),p=U.getDate(a,s,n+(t+1)*S*7-1)):"month"==k?(f=U.getDate(a,s+t*S,1),p=U.getDate(a,s+(t+1)*S,0)):"year"==k&&(f=U.getDate(a+t*S,0,1),p=U.getDate(a+(t+1)*S,0,0)),c(!0)},200)),pa(document).on("click",i)},onDayChange:function(e){var t=e.target,a=t!==b;o(),a&&(O=!1!==L&&pa(".mbsc-cal-txt-more",t).length,V={d:e.date,cell:U.outerMonthChange&&pa(t).hasClass("mbsc-cal-day-diff")?null:t,events:e.events})},onLabelTap:function(e){e.label&&(X("onEventSelect",{domEvent:e.domEvent,event:e.label,date:e.date}),M=!0)},onPageChange:function(e){o(),T=!0,y._isSetDate||y.setVal(e.firstDay)},onPageLoaded:function(e){var t=e.firstDay,a=e.lastDay;I&&(C?F&&H&&st(F,t)&&st(H,a)||(s(F=t,H=a),function(e,t){var a,s=(U.dateWheels||U.dateFormat).search(/m/i),n=(U.dateWheels||U.dateFormat).search(/y/i),i=U.getYear(e),o=U.getMonth(e),r=U.getYear(t),l=U.getMonth(t);"day"==k?a=re(U.dateFormat,e,U)+(1<S?" - "+re(U.dateFormat,t,U):""):"week"==k?a=re(U.dateFormat,e,U)+" - "+re(U.dateFormat,t,U):"month"==k?a=1==S?n<s?i+" "+U.monthNames[o]:U.monthNames[o]+" "+i:n<s?i+" "+U.monthNamesShort[o]+" - "+r+" "+U.monthNamesShort[l]:U.monthNamesShort[o]+" "+i+" - "+U.monthNamesShort[l]+" "+r:"year"==k&&(a=i+(1<S?" - "+r:"")),u.html(a)}(t,a)):(a="month"==k?U.getDate(U.getYear(t),U.getMonth(t)+S,0):"week"==k?U.getDate(U.getYear(t),U.getMonth(t),U.getDay(t)+7*S-1):t=y.getVal(!0),s(t,a,x)),G||st(h,t)||(l(h,E),E=!1)),L&&n(),T=!1},onPosition:function(e){if(m.onPosition.call(this,e),N&&N.position(),I&&P){w.addClass("mbsc-event-list-h");var t=
/* eslint-disable no-unused-vars */
function(e){var t=getComputedStyle(e);return e.innerHeight||e.clientHeight-parseFloat(t.paddingTop)-parseFloat(t.paddingBottom)}("inline"==U.display?W.parentNode:window)-e.popup.offsetHeight;v.style.height=200<t?t+"px":"",w.removeClass("mbsc-event-list-h"),G&&t&&(l(h,!0),G=!1)}},onHide:function(){m.onHide.call(this),N&&N.destroy(),pa(document).off("click",i)}})},f("eventcalendar",De);var jt,Wt=T&&!!window.Promise,Jt=[],Ut=[];function qt(e){Jt.length||e.show(),Jt.push(e)}function Bt(e,a,s,t){return va({display:a.display||"center",cssClass:"mbsc-alert",okText:a.okText,cancelText:a.cancelText,context:a.context,theme:a.theme,closeOnOverlayTap:!1,onBeforeClose:function(){e.shift()},onHide:function(e,t){s&&s(t._resolve),a.callback&&a.callback(t._resolve),t&&t.destroy(),Jt.length?Jt[0].show():Ut.length&&Ut[0].show(!1,!0)}},t)}function Kt(e){return(e.title?"<h2>"+e.title+"</h2>":"")+"<p>"+(e.message||"")+"</p>"}function Gt(e,t,a){qt(new Lt(e,Bt(Jt,t,a)))}function Xt(e,t,a){var s=new Lt(e,Bt(Jt,t,a,{buttons:["cancel","ok"],onSet:function(){s._resolve=!0}}));s._resolve=!1,qt(s)}function Zt(e,t,a){var s,n=new Lt(e,Bt(Jt,t,a,{buttons:["cancel","ok"],onMarkupReady:function(e,t){var a=t.settings;t._markup.find("label").addClass("mbsc-"+a.theme+(a.baseTheme?" mbsc-"+a.baseTheme:"")),s=t._markup.find("input")[0],setTimeout(function(){s.focus(),s.setSelectionRange(0,s.value.length)},300)},onSet:function(){n._resolve=s.value}}));n._resolve=null,qt(n)}function Qt(e,a,t,s,n){var i;!function(e){var t=Ut.length;Ut.push(e),Jt.length||(t?Ut[0].hide():e.show(!1,!0))}(new Lt(e,Bt(Ut,a,t,{display:a.display||"bottom",animate:n,cssClass:(s||"mbsc-snackbar")+(a.color?" mbsc-"+a.color:""),scrollLock:!1,focusTrap:!1,buttons:[],onMarkupReady:function(e,t){var a=t.settings;t._markup.find("button").addClass("mbsc-"+a.theme+(a.baseTheme?" mbsc-"+a.baseTheme:""))},onShow:function(e,t){jt=t,!1!==a.duration&&(i=setTimeout(function(){t&&t.hide()},a.duration||3e3)),a.button&&t.tap(pa(".mbsc-snackbar-btn",e.target),function(){t.hide(),a.button.action&&a.button.action.call(this)})},onClose:function(){jt=null,clearTimeout(i)}})))}function ea(e,t,a){Qt(e,t,a,"mbsc-toast","fade")}function ta(t,a,s){var e;return Wt?e=new Promise(function(e){t(a,s,e)}):t(a,s),e}Z.alert=function(e){var t=document.createElement("div");return t.innerHTML=Kt(e),ta(Gt,t,e)},Z.confirm=function(e){var t=document.createElement("div");return t.innerHTML=Kt(e),ta(Xt,t,e)},Z.prompt=function(e){var t=document.createElement("div");return t.innerHTML=Kt(e)+'<label class="mbsc-input">'+(e.label?'<span class="mbsc-label">'+e.label+"</span>":"")+'<input class="mbsc-control" tabindex="0" type="'+(e.inputType||"text")+'" placeholder="'+(e.placeholder||"")+'" value="'+(e.value||"")+'"></label>',ta(Zt,t,e)},Z.snackbar=function(e){var t=document.createElement("div"),a=e.button;return t.innerHTML='<div class="mbsc-snackbar-cont"><div class="mbsc-snackbar-msg">'+(e.message||"")+"</div>"+(a?'<button class="mbsc-snackbar-btn mbsc-btn mbsc-btn-flat">'+(a.icon?'<span class="mbsc-ic '+(a.text?"mbsc-btn-ic ":"")+"mbsc-ic-"+a.icon+'"></span>':"")+(a.text||"")+"</button>":"")+"</div>",ta(Qt,t,e)},Z.toast=function(e){var t=document.createElement("div");return t.innerHTML='<div class="mbsc-toast-msg">'+(e.message||"")+"</div>",ta(ea,t,e)},Z.notification={dismiss:function(){jt&&jt.hide()}};function aa(e,t){var a,s="",n=pa(e),i={},o=this;function r(){n.removeClass("mbsc-no-touch")}xa.call(this,e,t,!0),o.refresh=function(e){a.enhance&&Mt(n,i,a,e)},o._init=function(){Z.themes.form[a.theme]||(a.theme="mobiscroll"),n.hasClass("mbsc-form")||n.on("touchstart",r).show(),s&&n.removeClass(s),s="mbsc-form mbsc-no-touch mbsc-"+a.theme+(sa?" mbsc-form-hb":"")+(a.baseTheme?" mbsc-"+a.baseTheme:"")+(a.rtl?" mbsc-rtl":" mbsc-ltr")+("box"==a.inputStyle?" mbsc-form-box":"")+("outline"==a.inputStyle?" mbsc-form-outline":""),n.addClass(s).removeClass("mbsc-cloak"),o.refresh()},o._destroy=function(){for(var e in n.removeClass(s).off("touchstart",r),i)i[e].destroy()},o.controls=i,a=o.settings,o.init()}var sa="ios"==p&&7<v;aa.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_class:"form",_defaults:{tap:W,stopProp:!0,rtl:!1,enhance:!0}},H("[mbsc-enhance],[mbsc-form]",L.Form=aa,!0);function Ma(e,t){var i="",o=pa(e),a=this,r=a.settings;xa.call(this,e,t,!0),a._init=function(){var e=r.context,t=pa(e),a=t.find(".mbsc-ms-top .mbsc-ms"),s=t.find(".mbsc-ms-bottom .mbsc-ms"),n={};"body"==e?pa("body,html").addClass("mbsc-page-ctx"):t.addClass("mbsc-page-ctx"),i&&o.removeClass(i),a.length&&(n.paddingTop=a[0].offsetHeight),s.length&&(n.paddingBottom=s[0].offsetHeight),i="mbsc-page mbsc-"+r.theme+(r.baseTheme?" mbsc-"+r.baseTheme:"")+(r.rtl?" mbsc-rtl":" mbsc-ltr"),o.addClass(i).removeClass("mbsc-cloak").css(n)},a._destroy=function(){o.removeClass(i)},r=a.settings,a.init()}Ma.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_class:"page",_defaults:{context:"body"}},L.Page=Ma,Z.themes.page.mobiscroll={},H("[mbsc-page]",Ma),f("page",Ma,!1),f("form",aa,!1),f("progress",xt,!1),f("slider",gt,!1),f("stepper",ft,!1),f("switch",vt,!1),f("rating",Tt,!1);function Ca(c){var n,t,l,e,a=va({},c.settings),m=va(c.settings,ka,a),s=m.layout||(/top|bottom/.test(m.display)?"liquid":""),d="liquid"==s,i=m.readonly,o=pa(this),r=this.id+"_dummy",u=0,h=[],f=m.wheelArray||function r(e){var l=[];var t=1<e.length?e:e.children(m.itemSelector);t.each(function(e){var t=pa(this),a=t.clone();a.children("ul,ol").remove(),a.children(m.itemSelector).remove();var s=c._processMarkup?c._processMarkup(a):a.html().replace(/^\s\s*/,"").replace(/\s\s*$/,""),n=!!t.attr("data-invalid"),i={key:void 0===t.attr("data-val")||null===t.attr("data-val")?e:t.attr("data-val"),value:s,invalid:n,children:null},o="li"===m.itemSelector?t.children("ul,ol"):t.children(m.itemSelector);o.length&&(i.children=r(o)),l.push(i)});return l}(o),p=function(e){var t,a=[],s=e,n=!0,i=0;for(;n;)t=x(s),a[i++]=t.key,(n=t.children)&&(s=n);return a}(f);function b(e,t,a){for(var s,n=0,i=a,o=[];n<t;){var r=e[n];for(s in i)if(i[s].key==r){i=i[s].children;break}n++}for(n=0;n<i.length;)i[n].invalid&&o.push(i[n].key),n++;return o}function v(e,t,a){var s,n,i=0,o=!0,r=[[]],l=f;if(t)for(n=0;n<t;n++)d?r[0][n]={}:r[n]=[{}];for(;o;){for(d?r[0][i]=g(l,i):r[i]=[g(l,i)],n=0,s=null;n<l.length&&!s;)l[n].key==e[i]&&(void 0!==a&&i<=a||void 0===a)&&(s=l[n]),n++;(s=s||x(l))&&s.children?(l=s.children,i++):o=!1}return r}function x(e,t){if(!e)return!1;for(var a,s=0;s<e.length;)if(!(a=e[s++]).invalid)return t?s-1:a;return!1}function g(e,t){for(var a={data:[],label:m.labels&&m.labels[t]?m.labels[t]:t},s=0;s<e.length;)a.data.push({value:e[s].key,display:e[s].value}),s++;return a}function T(e){c._isVisible&&pa(".mbsc-sc-whl-w",c._markup).css("display","").slice(e).hide()}function y(e,t){for(var a,s,n,i=0,o=f,r=!0,l=[];r;){if(void 0!==e[i]&&i<=t)for(n=0,s=e[i],a=void 0;n<o.length&&void 0===a;)o[n].key!=e[i]||o[n].invalid||(a=n),n++;else s=o[a=x(o,!0)]&&o[a].key;l[i]=s,i++,r=!!o[a]&&o[a].children,o=o[a]&&o[a].children}return{lvl:i,nVector:l}}function _(e,t,a){var s,n,i=(t||0)+1,o=[],r={};for(n=v(e,null,t),s=0;s<e.length;s++)c._tempWheelArray[s]=e[s]=a.nVector[s]||0;for(;i<a.lvl;)r[i]=d?n[0][i]:n[i][0],o.push(i++);T(a.lvl),h=e.slice(0),o.length&&(l=!0,c.changeWheel(r))}return function e(t,a){var s;for(u=u<a?a:u,s=0;s<t.length;s++)t[s].children&&e(t[s].children,a+1)}(f,1),e=v(p,u),pa("#"+r).remove(),m.input?t=pa(m.input):m.showInput&&(t=pa('<input type="text" id="'+r+'" value="" class="'+m.inputClass+'" placeholder="'+(m.placeholder||"")+'" readonly />').insertBefore(o)),t&&c.attachShow(t),m.wheelArray||o.hide(),{wheels:e,anchor:t,layout:s,headerText:!1,setOnTap:1==u,formatValue:function(e){return void 0===n&&(n=y(e,e.length).lvl),e.slice(0,n).join(" ")},parseValue:function(e){return e?(e+"").split(" "):(m.defaultValue||p).slice(0)},onBeforeShow:function(){var e=c.getArrayVal(!0);h=e.slice(0),m.wheels=v(e,u,u),l=!0},onWheelGestureStart:function(e){m.readonly=function(e,t){for(var a=[];e;)a[--e]=!0;return a[t]=!1,a}(u,e.index)},onWheelAnimationEnd:function(e){var t=e.index,a=c.getArrayVal(!0),s=y(a,t);n=s.lvl,m.readonly=i,a[t]!=h[t]&&_(a,t,s)},onFill:function(e){n=void 0,t&&t.val(e.valueText)},validate:function(e){var t=e.values,a=e.index,s=y(t,t.length);return n=s.lvl,void 0===a&&(T(s.lvl),l||_(t,a,s)),l=!1,{disabled:function(e,t,a){for(var s=0,n=[];s<e;)n[s]=b(a,s,t),s++;return n}(n,f,t)}},onDestroy:function(){t&&pa("#"+r).remove(),o.show()}}}var ka={invalid:[],showInput:!0,inputClass:"",itemSelector:"li"};Me.image=function(e){return e.settings.enhance&&(e._processMarkup=function(e){var t=e.attr("data-icon");return e.children().each(function(e,t){(t=pa(t)).is("img")?pa('<div class="mbsc-img-c"></div>').insertAfter(t).append(t.addClass("mbsc-img")):t.is("p")&&t.addClass("mbsc-img-txt")}),t&&e.prepend('<div class="mbsc-ic mbsc-ic-'+t+'"></div'),e.html('<div class="mbsc-img-w">'+e.html()+"</div>"),e.html()}),Ca.call(this,e)},f("image",De);function Sa(e,t){var l,n,c,m,a,d,u,T,h,f,r,p,s,i,o,b,v,x,g,y,_,w,M,C,k,S,D,V,N,A,E,F,H,P,I,L,O,Y,z,$,R,j,W,J,U,q,B,K,G,X,Z,Q,ee,te,ae,se,ne,ie,oe,re,le,ce,me,de,ue,he,fe,pe,be,ve,xe,ge,Te,ye,_e,we,Me,Ce,ke,Se,De,Ve,Ne,Ae,Ee,Fe,He,Pe,Ie,Le,Oe,Ye,ze,$e,Re,je,We,Je,Ue,qe,Be,Ke,Ge,Xe,Ze,Qe=this,et=e,tt=pa(et),at=0,st=0,nt=0,it={},ot={},rt={};function lt(){be=Me=!1,He=m=0,Pe=new Date,G=f.width(),s=Yt(f),Q=s.index(X),Z=X[0].offsetHeight,nt=X[0].offsetTop,Je=Ue[X.attr("data-type")||"defaults"],Fe=Je.stages}function ct(e){var t;"touchstart"===e.type&&(T.removeClass("mbsc-no-touch"),ve=!0,clearTimeout(xe)),!ga(e,this)||l||at||Da||Qt||(q=!(a=l=!0),K="touchstart"===e.type,Ie=ua(e,"X"),Le=ua(e,"Y"),g=x=0,X=pa(this),t=X,lt(),Re=Je.actionable||X.hasClass("mbsc-lv-parent")||X.hasClass("mbsc-lv-back"),ae=X.offset().top,Re&&(c=setTimeout(function(){t.addClass(K?Na:""),D("onItemActivate",{target:t[0],domEvent:e})},120)),Qe.sortable&&!X.hasClass("mbsc-lv-back")&&(Qe.sortable.group||(he=X.nextUntil(".mbsc-lv-gr-title").filter(".mbsc-lv-item"),ge=X.prevUntil(".mbsc-lv-gr-title").filter(".mbsc-lv-item")),re=(Qe.sortable.group?f.children(te).eq(0):ge.length?ge.eq(-1):X)[0].offsetTop-nt,oe=(Qe.sortable.group?f.children(te).eq(-1):he.length?he.eq(-1):X)[0].offsetTop-nt,Qe.sortable.handle?pa(e.target).hasClass("mbsc-lv-handle")&&(clearTimeout(c),"Moz"===wa?(e.preventDefault(),bt()):We=setTimeout(function(){bt()},100)):We=setTimeout(function(){V.appendTo(X),V[0].style[wa+"Animation"]="mbsc-lv-fill "+(Se.sortDelay-100)+"ms linear",clearTimeout(C),clearTimeout(c),a=!1,We=setTimeout(function(){V[0].style[wa+"Animation"]="",bt()},Se.sortDelay-80)},80)),"mousedown"==e.type&&pa(document).on("mousemove",mt).on("mouseup",dt))}function mt(e){var t=!1,a=!0,s=m;if(l)if(k=ua(e,"X"),S=ua(e,"Y"),x=k-Ie,g=S-Le,clearTimeout(C),w||Ye||De||X.hasClass("mbsc-lv-back")||(10<Math.abs(g)?(De=!0,dt(va({},e,{type:"mousemove"==e.type?"mouseup":"touchend"})),clearTimeout(c)):7<Math.abs(x)&&ht()),Ye)e.preventDefault(),m=x/G*100,ft(s);else if(w){e.preventDefault();var n,i=Ke.scrollTop(),o=Math.max(re,Math.min(g+Xe,oe)),r=H?ae-Ze+i-Xe:ae;Ge+i<r+o+Z?(Ke.scrollTop(r+o-Ge+Z),n=!0):r+o<i&&(Ke.scrollTop(r+o),n=!0),n&&(Xe+=Ke.scrollTop()-i),de&&(Qe.sortable.multiLevel&&me.hasClass("mbsc-lv-parent")?de<nt+Z/4+o?t=!0:de<nt+Z-Z/4+o&&(y=me.addClass("mbsc-lv-item-hl"),a=!1):de<nt+Z/2+o&&(me.hasClass("mbsc-lv-back")?Qe.sortable.multiLevel&&(_=me.addClass("mbsc-lv-item-hl"),a=!1):t=!0),t&&(Te.insertAfter(me),me=$t(ye=me,"next"),_e=de,de=me.length&&me[0].offsetTop,h++)),!t&&_e&&(Qe.sortable.multiLevel&&ye.hasClass("mbsc-lv-parent")?nt+Z-Z/4+o<_e?t=!0:nt+Z/4+o<_e&&(y=ye.addClass("mbsc-lv-item-hl"),a=!1):nt+Z/2+o<_e&&(ye.hasClass("mbsc-lv-back")?Qe.sortable.multiLevel&&(_=ye.addClass("mbsc-lv-item-hl"),a=!1):t=!0),t&&(Te.insertBefore(ye),ye=$t(me=ye,"prev"),de=_e,_e=ye.length&&ye[0].offsetTop+ye[0].offsetHeight,h--)),a&&(y&&(y.removeClass("mbsc-lv-item-hl"),y=!1),_&&(_.removeClass("mbsc-lv-item-hl"),_=!1)),t&&D("onSortChange",{target:X[0],index:h}),Vt(X,o),D("onSort",{target:X[0],index:h})}else(5<Math.abs(x)||5<Math.abs(g))&&Nt()}function dt(e){var t,a,s,n=X;l&&(l=!1,Nt(),"mouseup"==e.type&&pa(document).off("mousemove",mt).off("mouseup",dt),De||(xe=setTimeout(function(){ve=!1},300)),(Ye||De||w)&&(be=!0),Ye?pt():w?(s=f,y?(Ht(X.detach()),a=rt[y.attr("data-ref")],h=Yt(a.child).length,y.removeClass("mbsc-lv-item-hl"),Se.navigateOnDrop?qt(y,function(){Qe.add(null,X,null,null,y,!0),Jt(X),vt(X,Q,s,!0)}):(Qe.add(null,X,null,null,y,!0),vt(X,Q,s,!0))):_?(Ht(X.detach()),a=rt[_.attr("data-back")],h=Yt(a.parent).index(a.item)+1,_.removeClass("mbsc-lv-item-hl"),Se.navigateOnDrop?qt(_,function(){Qe.add(null,X,h,null,f,!0),Jt(X),vt(X,Q,s,!0)}):(Qe.add(null,X,h,null,a.parent,!0),vt(X,Q,s,!0))):(t=Te[0].offsetTop-nt,Vt(X,t,6*Math.abs(t-Math.max(re,Math.min(g+Xe,oe))),function(){Ht(X),X.insertBefore(Te),vt(X,Q,s,h!==Q)})),w=!1):!De&&Math.abs(x)<5&&Math.abs(g)<5&&(q=!0,"touchend"===e.type&&Se.tap&&fa(e.target,ha(pa(e.target)),e)),clearTimeout(c),setTimeout(function(){n.removeClass(Na),D("onItemDeactivate",{target:n[0]})},100),De=!1,i=null)}function ut(e){var t;q&&(t="true"==X.attr("data-selected"),Je.tap&&Je.tap.call(et,{target:X,index:Q,domEvent:e},Qe),Re&&!X.hasClass(Na)&&(X.addClass(K?Na:""),D("onItemActivate",{target:X[0],domEvent:e})),Ve&&(ce?t?Zt(X):Xt(X):(Zt(pa(te,T).filter("."+Va)),Xt(X))),!1!==D("onItemTap",{target:X[0],index:Q,domEvent:e,selected:t})&&qt(X))}function ht(){(Ye=Pt(Je.swipe,{target:X[0],index:Q,direction:0<x?"right":"left"}))&&(Nt(),clearTimeout(c),Je.actions?(n=Wt(Je,x),le.html(Je.icons).show().children().css("width",n+"%"),J.hide(),pa(".mbsc-lv-ic-m",U).removeClass("mbsc-lv-ic-disabled"),pa(Je.leftMenu).each(wt),pa(Je.rightMenu).each(wt)):(J.show(),le.hide(),o=Je.start,i=Fe[o],we=Fe[o-1],ue=Fe[o+1]),X.addClass("mbsc-lv-item-swiping").removeClass(Na),je.css("line-height",Z+"px"),U.css({top:nt,height:Z,backgroundColor:Rt(x)}).addClass("mbsc-lv-stage-c-v").appendTo(f.parent()),Se.iconSlide&&X.append(J),D("onSlideStart",{target:X[0],index:Q}))}function ft(e){var t=!1;ke||(Je.actions?U.attr("class","mbsc-lv-stage-c-v mbsc-lv-stage-c mbsc-lv-"+(m<0?"right":"left")):(we&&(m<0?m<=we.percent:m<i.percent)?(ue=i,i=we,we=Fe[--o-1],t=!0):ue&&(m<0?m>i.percent:m>=ue.percent)&&(we=i,i=ue,ue=Fe[++o+1],t=!0),i&&(!t&&0<m!=e<=0||At(i,Se.iconSlide),t&&D("onStageChange",{target:X[0],index:Q,stage:i}))),Ne||(ke=!0,Ce=ia(kt)))}function pt(t){var e,a,s=!1;oa(Ce),ke=!1,Ne||kt(),Je.actions?10<Math.abs(m)&&n&&(Dt(X,m<0?-n:n,200),Da=s=!0,d=X,u=Q,pa(document).on("touchstart.mbsc-lv-conf mousedown.mbsc-lv-conf",function(e){e.preventDefault(),St(X,!0,t)})):m&&(Se.quickSwipe&&!Ne&&(e=(a=new Date-Pe)<300&&50<x,a<300&&x<-50?(Me=!0,At(i=Je.left,Se.iconSlide)):e&&(Me=!0,At(i=Je.right,Se.iconSlide))),i&&i.action&&(Pt(i.disabled,{target:X[0],index:Q})||(s=!0,(Da=Ne||Pt(i.confirm,{target:X[0],index:Q}))?(Dt(X,(m<0?-1:1)*J[0].offsetWidth*100/G,200,!0),Ct(i,X,Q,!1,t)):Mt(i,X,Q,t)))),s||St(X,!0,t),Ye=!1}function bt(){_=y=!(w=!0),Xe=0,h=Q,Se.vibrate&&ma(),me=$t(X,"next"),de=me.length&&me[0].offsetTop,ye=$t(X,"prev"),_e=ye.length&&ye[0].offsetTop+ye[0].offsetHeight,Te.height(Z).insertAfter(X),X.css({top:nt}).addClass("mbsc-lv-item-dragging").removeClass(Na).appendTo(M),D("onSortStart",{target:X[0],index:h})}function vt(t,a,s,e){t.removeClass("mbsc-lv-item-dragging"),Te.remove(),D("onSortEnd",{target:t[0],index:h}),Se.vibrate&&ma(),e&&(Qe.addUndoAction(function(e){Qe.move(t,a,null,e,s,!0)},!0),D("onSortUpdate",{target:t[0],index:h}))}function xt(){ve||(clearTimeout($),Da&&pa(document).trigger("touchstart"),O&&(Qe.close(L,Y),O=!1,L=null))}function gt(){clearTimeout(b),b=setTimeout(function(){Ge=Ke[0].innerHeight||Ke.innerHeight(),Ze=H?Ke.offset().top:0,l&&(nt=X[0].offsetTop,Z=X[0].offsetHeight,U.css({top:nt,height:Z}))},200)}function Tt(e){be&&(e.stopPropagation(),e.preventDefault(),be=!1)}function yt(){B||(clearTimeout(pe),pe=setTimeout(function(){var e=H?Ke[0].getBoundingClientRect().top+Ke.innerHeight():window.innerHeight,t=ie[0].getBoundingClientRect().top-3<e;!B&&t&&D("onListEnd")},250))}function _t(){if(w||!l){var a,e=Ke.scrollTop(),t=tt.offset().top,s=tt[0].offsetHeight,n=H?Ke.offset().top:e;pa(".mbsc-lv-gr-title",tt).each(function(e,t){pa(t).offset().top<n&&(a=t)}),t<n&&n<t+s?A.show().empty().append(pa(a).clone()):A.hide()}}function wt(e,t){Pt(t.disabled,{target:X[0],index:Q})&&pa(".mbsc-ic-"+t.icon,U).addClass("mbsc-lv-ic-disabled")}function Mt(e,t,a,s){var n,i={icon:"undo2",text:Se.undoText,action:function(){Qe.undo()}};e.undo&&(Qe.startActionTrack(),pa.isFunction(e.undo)&&Qe.addUndoAction(function(){e.undo.call(et,{target:t[0],index:a},Qe)}),qe=t.attr("data-ref")),n=e.action.call(et,{target:t[0],index:a},Qe),e.undo?(Qe.endActionTrack(),!1!==n&&Dt(t,+t.attr("data-pos")<0?-100:100,200),Te.height(Z).insertAfter(t),t.css("top",nt).addClass("mbsc-lv-item-undo"),le.hide(),J.show(),U.append(J),At(i),Ct(i,t,a,!0,s)):St(t,n,s)}function Ct(t,a,s,n,i){var o,r;Da=!0,pa(document).off(".mbsc-lv-conf").on("touchstart.mbsc-lv-conf mousedown.mbsc-lv-conf",function(e){e.preventDefault(),n&&Ft(a),St(a,!0,i)}),v||J.off(".mbsc-lv-conf").on("touchstart.mbsc-lv-conf mousedown.mbsc-lv-conf",function(e){e.stopPropagation(),o=ua(e,"X"),r=ua(e,"Y")}).on("touchend.mbsc-lv-conf mouseup.mbsc-lv-conf",function(e){e.preventDefault(),"touchend"===e.type&&da(),Math.abs(ua(e,"X")-o)<10&&Math.abs(ua(e,"Y")-r)<10&&(Mt(t,a,s,i),n&&(Be=null,Ft(a)))})}function kt(){Dt(X,He+100*x/G),ke=!1}function St(e,t,a){pa(document).off(".mbsc-lv-conf"),J.off(".mbsc-lv-conf"),!1!==t?Dt(e,0,"0"!==e.attr("data-pos")?200:0,!1,function(){Et(e,a),Ht(e)}):Et(e,a),Da=!1}function Dt(e,t,a,s,n){t=Math.max("right"==Ye?0:-100,Math.min(t,"left"==Ye?0:100)),Oe=e[0].style,e.attr("data-pos",t),Oe[wa+"Transform"]="translate3d("+(s?G*t/100+"px":t+"%")+",0,0)",Oe[wa+"Transition"]=_a+"transform "+(a||0)+"ms",n&&(at++,setTimeout(function(){n(),at--},a)),m=t}function Vt(e,t,a,s){t=Math.max(re,Math.min(t,oe)),(Oe=e[0].style)[wa+"Transform"]="translate3d(0,"+t+"px,0)",Oe[wa+"Transition"]=_a+"transform "+(a||0)+"ms ease-out",s&&(at++,setTimeout(function(){s(),at--},a))}function Nt(){clearTimeout(We),!a&&Qe.sortable&&(a=!0,V.remove())}function At(e,t){var a=Pt(e.text,{target:X[0],index:Q})||"";Pt(e.disabled,{target:X[0],index:Q})?U.addClass("mbsc-lv-ic-disabled"):U.removeClass("mbsc-lv-ic-disabled"),U.css("background-color",e.color||(0===e.percent?Rt(m):Fa)),J.attr("class","mbsc-lv-ic-c mbsc-lv-ic-"+(t?"move-":"")+(m<0?"right":"left")),W.attr("class"," mbsc-lv-ic-s mbsc-lv-ic mbsc-ic mbsc-ic-"+(e.icon||"none")),je.attr("class","mbsc-lv-ic-text"+(e.icon?"":" mbsc-lv-ic-text-only")+(a?"":" mbsc-lv-ic-only")).html(a||"&nbsp;"),Se.animateIcons&&(Me?W.addClass("mbsc-lv-ic-v"):setTimeout(function(){W.addClass("mbsc-lv-ic-a")},10))}function Et(e,t){l||(W.attr("class","mbsc-lv-ic-s mbsc-lv-ic mbsc-ic mbsc-ic-none"),U.attr("style","").removeClass("mbsc-lv-stage-c-v"),je.html("")),U.removeClass("mbsc-lv-left mbsc-lv-right"),e&&(D("onSlideEnd",{target:e[0],index:Q}),t&&t())}function Ft(e){e.css("top","").removeClass("mbsc-lv-item-undo"),Be?Qe.animate(Te,"collapse",function(){Te.remove()}):Te.remove(),Et(),Be=qe=null}function Ht(e){(Oe=e[0].style)[wa+"Transform"]="",Oe[wa+"Transition"]="",Oe.top="",e.removeClass("mbsc-lv-item-swiping")}function Pt(e,t){return pa.isFunction(e)?e.call(this,t,Qe):e}function It(e){return Ve&&!e.hasClass("mbsc-lv-parent")&&!e.hasClass("mbsc-lv-back")}function Lt(e){var t=e.attr("data-ref"),a=e.attr("data-role"),s=Ue[e.attr("data-type")||"defaults"],n=It(e)&&"true"==e.attr("data-selected");if(t||(t=Ea++,e.attr("data-ref",t)),rt[t]={item:e,child:e.children(ne),parent:e.parent(),ref:e.parent()[0]===et?null:e.parent().parent().attr("data-ref")},e.addClass("list-divider"==a?"mbsc-lv-gr-title":"mbsc-lv-item"+(s.actionable?" mbsc-lv-item-actionable":"")+(n?" "+Va:"")),e.attr("aria-selected",n?"true":"false"),Qe.sortable.handle&&"list-divider"!=a&&!e.children(".mbsc-lv-handle-c").length&&e.append(P),Se.enhance&&!e.hasClass("mbsc-lv-item-enhanced")){var i=e.attr("data-icon"),o=e.find("img").eq(0).addClass("mbsc-lv-img");o.is(":first-child")?e.addClass("mbsc-lv-img-"+(Se.rtl?"right":"left")):o.length&&e.addClass("mbsc-lv-img-"+(Se.rtl?"left":"right")),e.addClass("mbsc-lv-item-enhanced").children().each(function(e,t){(t=pa(t)).is("p, h1, h2, h3, h4, h5, h6")&&t.addClass("mbsc-lv-txt")}),i&&e.addClass("mbsc-lv-item-ic-"+(e.attr("data-icon-align")||(Se.rtl?"right":"left"))).append('<div class="mbsc-lv-item-ic mbsc-ic mbsc-ic-'+i+'"></div>')}}function Ot(e){pa(te,e).not(".mbsc-lv-back").each(function(){Lt(pa(this))}),pa(ne,e).not(".mbsc-lv").addClass("mbsc-lv").prepend(R).parent().addClass("mbsc-lv-parent mbsc-lv-item-actionable").prepend(j),pa(".mbsc-lv-back",e).each(function(){pa(this).attr("data-back",pa(this).parent().parent().attr("data-ref"))})}function Yt(e){return e.children(te).not(".mbsc-lv-back").not(".mbsc-lv-removed").not(".mbsc-lv-ph")}function zt(e){return"object"!==na(e)&&(e=pa(te,T).filter('[data-id="'+e+'"]')),pa(e)}function $t(e,t){for(e=e[t]();e.length&&(!e.hasClass("mbsc-lv-item")||e.hasClass("mbsc-lv-ph")||e.hasClass("mbsc-lv-item-dragging"));){if(!Qe.sortable.group&&e.hasClass("mbsc-lv-gr-title"))return!1;e=e[t]()}return e}function Rt(e){return(0<e?Je.right:Je.left).color||Fa}function jt(e){return la(e)?e+"":0}function Wt(e,t){return+(t<0?jt((e.actionsWidth||0).right)||jt(e.actionsWidth)||jt(Se.actionsWidth.right)||jt(Se.actionsWidth):jt((e.actionsWidth||0).left)||jt(e.actionsWidth)||jt(Se.actionsWidth.left)||jt(Se.actionsWidth))}function Jt(e,t){if(e){var a=Ke.scrollTop(),s=e.is(".mbsc-lv-item")?e[0].offsetHeight:0,n=e.offset().top+(H?a-Ze:0);t?(n<a||a+Ge<n+s)&&Ke.scrollTop(n):n<a?Ke.scrollTop(n):a+Ge<n+s&&Ke.scrollTop(Math.min(n,n+s-Ge/2))}}function Ut(e,t,a,s,n){var i=t.parent(),o=t.prev();s=s||ra,o[0]===J[0]&&(o=J.prev()),Se.rtl&&(e="l"===e?"r":"l"),f[0]!==t[0]?(D("onNavStart",{level:st,direction:e,list:t[0]}),Ae.prepend(t.addClass("mbsc-lv-v mbsc-lv-sl-new")),Jt(T),Bt(Ae,"mbsc-lv-sl-"+e,function(){f.removeClass("mbsc-lv-sl-curr"),t.removeClass("mbsc-lv-sl-new").addClass("mbsc-lv-sl-curr"),r&&r.length?f.removeClass("mbsc-lv-v").insertAfter(r):p.append(f.removeClass("mbsc-lv-v")),r=o,p=i,f=t,Jt(a,n),s.call(et,a),D("onNavEnd",{level:st,direction:e,list:t[0]})})):(Jt(a,n),s.call(et,a))}function qt(e,t){at||(e.hasClass("mbsc-lv-parent")?(st++,Ut("r",rt[e.attr("data-ref")].child,null,t)):e.hasClass("mbsc-lv-back")&&(st--,Ut("l",rt[e.attr("data-back")].parent,rt[e.attr("data-back")].item,t)))}function Bt(e,t,a){var s;function n(){clearTimeout(s),at--,e.off(ya,n).removeClass(t),a.call(et,e)}a=a||ra,Se.animation&&"mbsc-lv-item-none"!==t?(at++,e.on(ya,n).addClass(t),s=setTimeout(n,250)):a.call(et,e)}function Kt(e,t){var a,s=e.attr("data-ref");a=ot[s]=ot[s]||[],t&&a.push(t),e.attr("data-action")||(t=a.shift())&&(e.attr("data-action",1),t(function(){e.removeAttr("data-action"),a.length?Kt(e):delete ot[s]}))}function Gt(a,s,n){var i,o;a&&a.length&&(i=100/(a.length+2),pa.each(a,function(e,t){void 0===t.key&&(t.key=Ee++),void 0===t.percent&&(t.percent=s*i*(e+1),n&&((o=va({},t)).key=Ee++,o.percent=-i*(e+1),a.push(o),it[o.key]=o)),it[t.key]=t}))}function Xt(e){It(e)&&e.addClass(Va).attr("data-selected","true").attr("aria-selected","true")}function Zt(e){e.removeClass(Va).removeAttr("data-selected").removeAttr("aria-selected")}xa.call(this,e,t,!0),Qe.animate=function(e,t,a){Bt(e,"mbsc-lv-item-"+t,a)},Qe.add=function(e,t,a,s,n,i){var o,r,l,c,m,d,u="",h=void 0===n?tt:zt(n),f=h,p="object"!==na(t)?pa("<"+ee+' data-ref="'+Ea+++'" data-id="'+e+'">'+t+"</"+ee+">"):pa(t),b=p[0],v=b.style,x=p.attr("data-pos")<0?"left":"right",g=p.attr("data-ref");s=s||ra,g||(g=Ea++,p.attr("data-ref",g)),Lt(p),i||Qe.addUndoAction(function(e){c?Qe.navigate(h,function(){f.remove(),h.removeClass("mbsc-lv-parent").children(".mbsc-lv-arr").remove(),m.child=h.children(ne),Qe.remove(p,null,e,!0)}):Qe.remove(p,null,e,!0)},!0),Kt(p,function(t){Ht(p.css("top","").removeClass("mbsc-lv-item-undo")),h.is(te)?(d=h.attr("data-ref"),h.children(ne).length||(c=!0,h.append("<"+se+"></"+se+">"))):d=h.children(".mbsc-lv-back").attr("data-back"),(m=rt[d])&&(m.child.length?f=m.child:(h.addClass("mbsc-lv-parent").prepend(j),f=h.children(ne).prepend(R).addClass("mbsc-lv"),m.child=f,pa(".mbsc-lv-back",h).attr("data-back",d))),rt[g]={item:p,child:p.children(ne),parent:f,ref:d},l=Yt(f),r=l.length,null==a&&(a=r),i&&(u="mbsc-lv-item-new-"+(i?x:"")),Ot(p.addClass(u)),!1!==a&&(r?a<r?p.insertBefore(l.eq(a)):p.insertAfter(l.eq(r-1)):(o=pa(".mbsc-lv-back",f)).length?p.insertAfter(o):f.append(p)),T.trigger("mbsc-refresh"),Se.animateAddRemove&&f.hasClass("mbsc-lv-v")?(v.height=b.offsetHeight+"px",Qe.animate(p,i&&qe===g?"none":"expand",function(e){Qe.animate(e,i?"add-"+x:"pop-in",function(e){v.height="",s.call(et,e.removeClass(u)),t()})})):(s.call(et,p.removeClass(u)),t()),D("onItemAdd",{target:b})})},Qe.swipe=function(e,t,a,s,n){var i;e=zt(e),X=e,v=s,l=Ne=!0,a=void 0===a?300:a,x=0<t?1:-1,lt(),ht(),Dt(e,t,a),clearTimeout($e),clearInterval(ze),ze=setInterval(function(){i=m,m=Ta(e)/G*100,ft(i)},10),$e=setTimeout(function(){clearInterval(ze),i=m,m=t,ft(i),pt(n),l=Ne=v=!1},a)},Qe.openStage=function(e,t,a,s){it[t]&&Qe.swipe(e,it[t].percent,a,s)},Qe.openActions=function(e,t,a,s){e=zt(e);var n=Wt(Ue[e.attr("data-type")||"defaults"],"left"==t?-1:1);Qe.swipe(e,"left"==t?-n:n,a,s)},Qe.close=function(e,t){Qe.swipe(e,0,t)},Qe.remove=function(e,a,s,n){var i,o,t,r,l,c,m;s=s||ra,l=(i=zt(e)).attr("data-ref"),i.length&&rt[l]&&(o=i.parent(),r=Yt(o).index(i),m=i[0].style,function t(e){e&&(c=c||e.hasClass("mbsc-lv-v"),e.children("[data-ref]").each(function(){var e=pa(this).attr("data-ref");rt[e]&&(t(rt[e].child),delete rt[e])}))}(rt[l].child),c&&(t=Se.animation,Se.animation=!1,Qe.navigate(i),Se.animation=t),delete rt[l],n||(i.attr("data-ref")===qe&&(Be=!0),Qe.addUndoAction(function(e){Qe.add(null,i,r,e,o,!0)},!0)),Kt(i,function(t){a=a||(i.attr("data-pos")<0?"left":"right"),Se.animateAddRemove&&o.hasClass("mbsc-lv-v")?Qe.animate(i.addClass("mbsc-lv-removed"),n?"pop-out":"remove-"+a,function(e){m.height=e[0].offsetHeight+"px",Qe.animate(e,"collapse",function(e){m.height="",Ht(e.removeClass("mbsc-lv-removed")),!1!==s.call(et,e)&&e.remove(),t()})}):(!1!==s.call(et,i)&&i.remove(),t()),D("onItemRemove",{target:i[0]})}))},Qe.move=function(e,t,a,s,n,i){e=zt(e),i||Qe.startActionTrack(),U.append(J),Qe.remove(e,a,null,i),Qe.add(null,e,t,s,n,i),i||Qe.endActionTrack()},Qe.navigate=function(e,t){var a,s;e=zt(e),a=rt[e.attr("data-ref")],s=function(e){for(var t=0,a=rt[e.attr("data-ref")];a&&a.ref;)t++,a=rt[a.ref];return t}(e),a&&(Ut(st<=s?"r":"l",a.parent,e,t,!0),st=s)},Qe.showLoading=function(){B=!0,ie.addClass("mbsc-show-lv-loading"),Ke.scrollTop(H?Ke[0].scrollHeight:pa(Se.context)[0].scrollHeight)},Qe.hideLoading=function(){ie.removeClass("mbsc-show-lv-loading"),setTimeout(function(){B=!1},100)},Qe.select=function(e){ce||Zt(pa(te,T).filter("."+Va)),Xt(zt(e))},Qe.deselect=function(e){Zt(zt(e))},Qe._processSettings=function(){tt.is("[mbsc-enhance]")&&(E=!0,tt.removeAttr("mbsc-enhance"))},Qe._init=function(){var e,t,a,s=tt.find(ne).length?"left":"right",n=0,i="",o="",r="";se=Se.listNode,ne=Se.listSelector,ee=Se.itemNode,te=Se.itemSelector,ce="multiple"==Se.select,Ve="off"!=Se.select,"group"===(a=Se.sort||Se.sortable||!1)&&(a={group:!1,multiLevel:!0}),!0===a&&(a={group:!0,multiLevel:!0,handle:Se.sortHandle}),a&&void 0===a.handle&&(a.handle=Se.sortHandle),a.handle&&(F=!0===a.handle?s:a.handle,P='<div class="mbsc-lv-handle-c mbsc-lv-item-h-'+F+' mbsc-lv-handle"><div class="'+Se.handleClass+' mbsc-lv-handle-bar-c mbsc-lv-handle">'+Se.handleMarkup+"</div></div>"),R="<"+ee+' class="mbsc-lv-item mbsc-lv-back mbsc-lv-item-actionable">'+Se.backText+'<div class="mbsc-lv-arr mbsc-lv-ic mbsc-ic '+Se.leftArrowClass+'"></div></'+ee+">",j='<div class="mbsc-lv-arr mbsc-lv-ic mbsc-ic '+Se.rightArrowClass+'"></div>',e="mbsc-no-touch mbsc-lv-cont mbsc-lv-"+Se.theme+" mbsc-"+Se.theme+(Aa?" mbsc-lv-hb":"")+(Se.rtl?" mbsc-lv-rtl mbsc-rtl":" mbsc-ltr")+(Se.baseTheme?" mbsc-lv-"+Se.baseTheme+" mbsc-"+Se.baseTheme:"")+(Se.animateIcons?" mbsc-lv-ic-anim":"")+(Se.striped?" mbsc-lv-alt-row":"")+(Se.fixedHeader?" mbsc-lv-has-fixed-header":"")+(a.handle?" mbsc-lv-handle-"+F:""),Qe.sortable=a||!1,T?(T.attr("class",e),pa(".mbsc-lv-handle-c",T).remove(),pa(te,T).not(".mbsc-lv-back").removeClass("mbsc-lv-item"),Ke.off("orientationchange resize",gt),fe&&Ke.off("scroll touchmove",fe),Ke.off("scroll touchmove",yt)):(i+='<div class="mbsc-lv-multi-c"></div>',i+='<div class="mbsc-lv-ic-c"><div class="mbsc-lv-ic-s mbsc-lv-ic mbsc-ic mbsc-ic-none"></div><div class="mbsc-lv-ic-text"></div></div>',tt.addClass("mbsc-lv mbsc-lv-v mbsc-lv-root").removeClass("mbsc-cloak").show(),U=pa('<div class="mbsc-lv-stage-c">'+i+"</div>"),J=pa(".mbsc-lv-ic-c",U),le=pa(".mbsc-lv-multi-c",U),W=pa(".mbsc-lv-ic-s",U),je=pa(".mbsc-lv-ic-text",U),Te=pa("<"+ee+' class="mbsc-lv-item mbsc-lv-ph"></'+ee+">"),V=pa('<div class="mbsc-lv-fill-item"></div>'),T=pa('<div class="'+e+'"><'+se+' class="mbsc-lv mbsc-lv-dummy"></'+se+'><div class="mbsc-lv-sl-c"></div><div class="mbsc-lv-loading"><span class="mbsc-ic mbsc-ic-'+(Se.loadingIcon||"loop2")+'"></span></div></div>'),M=pa(".mbsc-lv-dummy",T),ie=pa(".mbsc-lv-loading",T),T.insertAfter(tt),gt(),T.on("touchstart mousedown",".mbsc-lv-item",ct).on("touchmove",".mbsc-lv-item",mt).on("touchend touchcancel",".mbsc-lv-item",dt).on("click",".mbsc-lv-item",ut),et.addEventListener("click",Tt,!0),T.on("touchstart mousedown",".mbsc-lv-ic-m",function(e){v||(e.stopPropagation(),e.preventDefault()),Ie=ua(e,"X"),Le=ua(e,"Y")}).on("touchend mouseup",".mbsc-lv-ic-m",function(e){v||("touchend"===e.type&&da(),Da&&!pa(this).hasClass("mbsc-lv-ic-disabled")&&Math.abs(ua(e,"X")-Ie)<10&&Math.abs(ua(e,"Y")-Le)<10&&Mt((m<0?Je.rightMenu:Je.leftMenu)[pa(this).index()],d,u))}),Ae=pa(".mbsc-lv-sl-c",T).append(tt.addClass("mbsc-lv-sl-curr")).attr("data-ref",Ea++),f=tt,p=T),H="body"!==Se.context,(Ke=pa(H?Se.context:window)).on("orientationchange resize",gt),Ke.on("scroll touchmove",yt),Ee=0,(Ue=Se.itemGroups||{}).defaults={swipeleft:Se.swipeleft,swiperight:Se.swiperight,stages:Se.stages,actions:Se.actions,actionsWidth:Se.actionsWidth,actionable:Se.actionable},Ot(tt),pa.each(Ue,function(e,a){if(a.swipe=void 0!==a.swipe?a.swipe:Se.swipe,a.actionable=void 0!==a.actionable?a.actionable:Se.actionable,a.stages=a.stages||[],Gt(a.stages,1,!0),Gt(a.stages.left,1),Gt(a.stages.right,-1),(a.stages.left||a.stages.right)&&(a.stages=[].concat(a.stages.left||[],a.stages.right||[])),N=!1,a.stages.length||(a.swipeleft&&a.stages.push({percent:-30,action:a.swipeleft}),a.swiperight&&a.stages.push({percent:30,action:a.swiperight})),pa.each(a.stages,function(e,t){if(0===t.percent)return!(N=!0)}),N||a.stages.push({percent:0}),a.stages.sort(function(e,t){return e.percent-t.percent}),pa.each(a.stages,function(e,t){if(0===t.percent)return a.start=e,!1}),N?a.left=a.right=a.stages[a.start]:(a.left=a.stages[a.start-1]||{},a.right=a.stages[a.start+1]||{}),a.actions){for(a.leftMenu=a.actions.left||a.actions,a.rightMenu=a.actions.right||a.leftMenu,r=o="",n=0;n<a.leftMenu.length;n++)o+="<div "+(a.leftMenu[n].color?'style="background-color: '+a.leftMenu[n].color+'"':"")+' class="mbsc-lv-ic-m mbsc-lv-ic mbsc-ic mbsc-ic-'+a.leftMenu[n].icon+'">'+(a.leftMenu[n].text||"")+"</div>";for(n=0;n<a.rightMenu.length;++n)r+="<div "+(a.rightMenu[n].color?'style="background-color: '+a.rightMenu[n].color+'"':"")+' class="mbsc-lv-ic-m mbsc-lv-ic mbsc-ic mbsc-ic-'+a.rightMenu[n].icon+'">'+(a.rightMenu[n].text||"")+"</div>";a.actions.left&&(a.swipe=a.actions.right?a.swipe:"right"),a.actions.right&&(a.swipe=a.actions.left?a.swipe:"left"),a.icons='<div class="mbsc-lv-multi mbsc-lv-multi-ic-left">'+o+'</div><div class="mbsc-lv-multi mbsc-lv-multi-ic-right">'+r+"</div>"}}),Se.fixedHeader&&(t="mbsc-lv-fixed-header"+(H?" mbsc-lv-fixed-header-ctx mbsc-lv-"+Se.theme+" mbsc-"+Se.theme+(Se.baseTheme?" mbsc-lv-"+Se.baseTheme+" mbsc-"+Se.baseTheme:""):""),A?(A.attr("class",t),_t()):A=pa('<div class="'+t+'"></div>'),H?Ke.before(A):T.prepend(A),fe=ca(_t,200),Ke.on("scroll touchmove",fe)),Se.hover&&(Y||T.on("mouseover.mbsc-lv",".mbsc-lv-item",function(){L&&L[0]==this||(xt(),L=pa(this),Ue[L.attr("data-type")||"defaults"].actions&&($=setTimeout(function(){ve?L=null:(O=!0,Qe.openActions(L,I,Y,!1))},z)))}).on("mouseleave.mbsc-lv",xt),Y=Se.hover.time||200,z=Se.hover.timeout||200,I=Se.hover.direction||Se.hover||"right"),E&&T.attr("mbsc-enhance",""),T.trigger("mbsc-enhance",[{theme:Se.theme,lang:Se.lang}])},Qe._destroy=function(){var e;p.append(f),H&&A&&A.remove(),E&&(tt.attr("mbsc-enhance",""),(e=ba[T[0].id])&&e.destroy()),et.removeEventListener("click",Tt,!0),T.find(".mbsc-lv-txt,.mbsc-lv-img").removeClass("mbsc-lv-txt mbsc-lv-img"),T.find(ne).removeClass("mbsc-lv mbsc-lv-v mbsc-lv-root mbsc-lv-sl-curr").find(te).removeClass("mbsc-lv-gr-title mbsc-lv-item mbsc-lv-item-enhanced mbsc-lv-parent mbsc-lv-img-left mbsc-lv-img-right mbsc-lv-item-ic-left mbsc-lv-item-ic-right").removeAttr("data-ref"),pa(".mbsc-lv-back,.mbsc-lv-handle-c,.mbsc-lv-arr,.mbsc-lv-item-ic",T).remove(),tt.insertAfter(T),T.remove(),U.remove(),Ke.off("orientationchange resize",gt),Ke.off("scroll touchmove",yt),fe&&Ke.off("scroll touchmove",fe)};var Qt,ea=[],ta=[],aa=[],sa=0;Qe.startActionTrack=function(){sa||(aa=[]),sa++},Qe.endActionTrack=function(){--sa||ta.push(aa)},Qe.addUndoAction=function(e,t){var a={action:e,async:t};sa?aa.push(a):(ta.push([a]),ta.length>Se.undoLimit&&ta.shift())},Qe.undo=function(){var e,t,a;function s(){t<0?(Qt=!1,n()):(e=a[t],t--,e.async?e.action(s):(e.action(),s()))}function n(){(a=ea.shift())&&(Qt=!0,t=a.length-1,s())}ta.length&&ea.push(ta.pop()),Qt||n()},Se=Qe.settings,D=Qe.trigger,Qe.init()}var Da,Va="mbsc-selected",Na="mbsc-lv-item-active",Aa="ios"==p&&7<v,Ea=1,Fa="transparent";Sa.prototype={_class:"listview",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_defaults:{context:"body",actionsWidth:90,sortDelay:250,undoLimit:10,tap:W,swipe:!0,quickSwipe:!0,animateAddRemove:!0,animateIcons:!0,animation:!0,revert:!0,vibrate:!0,actionable:!0,handleClass:"",handleMarkup:'<div class="mbsc-lv-handle-bar mbsc-lv-handle"></div><div class="mbsc-lv-handle-bar mbsc-lv-handle"></div><div class="mbsc-lv-handle-bar mbsc-lv-handle"></div>',listNode:"ul",listSelector:"ul,ol",itemNode:"li",itemSelector:"li",leftArrowClass:"mbsc-ic-arrow-left4",rightArrowClass:"mbsc-ic-arrow-right4",backText:"Back",undoText:"Undo",stages:[],select:"off"}},f("listview",L.ListView=Sa,!(Z.themes.listview.mobiscroll={leftArrowClass:"mbsc-ic-arrow-left5",rightArrowClass:"mbsc-ic-arrow-right5"}));var Ha={batch:50,min:0,max:100,defaultUnit:"",units:null,unitNames:null,invalid:[],sign:!1,step:.05,scale:2,convert:function(e){return e},signText:"&nbsp;",wholeText:"Whole",fractionText:"Fraction",unitText:"Unit"};Me.measurement=function(b){var a,v,x,g,T,y,_,w,M,C,k,S,e,t,s=va({},b.settings),D=va(b.settings,Ha,s),n={},i=[[]],V={},N={},o={},A=[],E=D.sign,F=D.units&&D.units.length,H=F?D.defaultUnit||D.units[0]:"",r=[],P=D.step<1,I=1<D.step?D.step:1,l=P?Math.max(D.scale,(D.step+"").split(".")[1].length):1,c=Math.pow(10,l),L=Math.round(P?D.step*c:D.step),m=0,d=0,O=0;function u(e){return Math.max(M,Math.min(C,P?e<0?Math.ceil(e):Math.floor(e):$(Math.round(e-m),L)+m))}function h(e){return P?$((Math.abs(e)-Math.abs(u(e)))*c-d,L)+d:0}function Y(e){var t=u(e),a=h(e);return c<=a&&(e<0?t--:t++,a=0),[e<0?"-":"+",t,a]}function z(e){var t=+e[T],a=P?e[g]/c*(t<0?-1:1):0;return(E&&"-"==e[0]?-1:1)*(t+a)}function $(e,t){return Math.round(e/t)*t}function R(e,t,a){return t!==a&&D.convert?D.convert.call(this,e,t,a):e}function j(e){var t,a;_=R(D.min,H,e),w=R(D.max,H,e),P?(M=_<0?Math.ceil(_):Math.floor(_),C=w<0?Math.ceil(w):Math.floor(w),k=h(_),S=h(w)):(M=Math.round(_),C=Math.round(w),C=M+Math.floor((C-M)/L)*L,m=M%L),t=M,a=C,E&&(a=Math.abs(t)>Math.abs(a)?Math.abs(t):Math.abs(a),t=t<0?0:t),N.min=t<0?Math.ceil(t/I):Math.floor(t/I),N.max=a<0?Math.ceil(a/I):Math.floor(a/I)}function f(e){return z(e).toFixed(P?l:0)+(F?" "+r[e[y]]:"")}if(b.setVal=function(e,t,a,s,n){b._setVal(pa.isArray(e)?f(e):e,t,a,s,n)},D.units)for(t=0;t<D.units.length;++t)e=D.units[t],r.push(D.unitNames&&D.unitNames[e]||e);if(E)if(E=!1,F)for(t=0;t<D.units.length;t++)R(D.min,H,D.units[t])<0&&(E=!0);else E=D.min<0;if(E&&(i[0].push({data:["-","+"],label:D.signText}),O++),N={label:D.wholeText,data:function(e){return M%I+e*I},getIndex:function(e){return Math.round((e-M%I)/I)}},i[0].push(N),T=O++,j(H),P){for(i[0].push(o),o.data=[],o.label=D.fractionText,t=d;t<c;t+=L)A.push(t),o.data.push({value:t,display:"."+oe(t,l)});g=O++,a=Math.ceil(100/L),D.invalid&&D.invalid.length&&(pa.each(D.invalid,function(e,t){var a=0<t?Math.floor(t):Math.ceil(t);0===a&&(a=t<=0?-.001:.001),V[a]=(V[a]||0)+1,0===t&&(V[a=.001]=(V[a]||0)+1)}),pa.each(V,function(e,t){t<a?delete V[e]:V[e]=e}))}if(F){for(n={data:[],label:D.unitText,cssClass:"mbsc-msr-whl-unit",circular:!1},t=0;t<D.units.length;t++)n.data.push({value:t,display:r[t]});i[0].push(n)}return y=O,{wheels:i,minWidth:E&&P?70:80,showLabel:!1,formatValue:f,compClass:"mbsc-msr mbsc-sc",parseValue:function(e){var t,a=((("number"==typeof e?e+"":e)||D.defaultValue)+"").split(" "),s=+a[0],n=[],i="";return F&&(i=-1==(i=-1==(i=pa.inArray(a[1],r))?pa.inArray(H,D.units):i)?0:i),j(x=F?D.units[i]:""),(t=Y(s=ue(s=isNaN(s)?0:s,_,w)))[1]=ue(t[1],M,C),v=s,E&&(n[0]=t[0],t[1]=Math.abs(t[1])),n[T]=t[1],P&&(n[g]=t[2]),F&&(n[y]=i),n},onCancel:function(){v=void 0},validate:function(e){var a,s,t,n,i,o=e.values,r=e.index,l=e.direction,c={},m=[],d={},u=F?D.units[o[y]]:"";if(E&&0===r&&(v=Math.abs(v)*("-"==o[0]?-1:1)),(r===T||r===g&&P||void 0===v||void 0===r)&&(v=z(o),x=u),(F&&r===y&&x!==u||void 0===r)&&(j(u),v=R(v,x,u),x=u,s=Y(v),void 0!==r&&(d[T]=N,b.changeWheel(d)),E&&(o[0]=s[0])),m[T]=[],E)for(m[0]=[],0<_&&(m[0].push("-"),o[0]="+"),w<0&&(m[0].push("+"),o[0]="-"),i=Math.abs("-"==o[0]?M:C),O=i+I;O<i+20*I;O+=I)m[T].push(O),c[O]=!0;if(v=ue(v,_,w),s=Y(v),t=E?Math.abs(s[1]):s[1],a=E?"-"==o[0]:v<0,o[T]=t,a&&(s[0]="-"),P&&(o[g]=s[2]),pa.each(P?V:D.invalid,function(e,t){if(E&&a){if(!(t<=0))return;t=Math.abs(t)}t=$(R(t,H,u),P?1:L),c[t]=!0,m[T].push(t)}),o[T]=b.getValidValue(T,t,l,c),s[1]=o[T]*(E&&a?-1:1),P){m[g]=[];var h=E?o[0]+o[1]:(v<0?"-":"+")+Math.abs(s[1]),f=(_<0?"-":"+")+Math.abs(M),p=(w<0?"-":"+")+Math.abs(C);h===f&&pa(A).each(function(e,t){(a?k<t:t<k)&&m[g].push(t)}),h===p&&pa(A).each(function(e,t){(a?t<S:S<t)&&m[g].push(t)}),pa.each(D.invalid,function(e,t){n=Y(R(t,H,u)),(s[0]===n[0]||0===s[1]&&0===n[1]&&0===n[2])&&s[1]===n[1]&&m[g].push(n[2])})}return{disabled:m,valid:o}}}};var Pa={min:0,max:100,defaultUnit:"km",units:["m","km","in","ft","yd","mi"]},Ia={mm:.001,cm:.01,dm:.1,m:1,dam:10,hm:100,km:1e3,in:.0254,ft:.3048,yd:.9144,ch:20.1168,fur:201.168,mi:1609.344,lea:4828.032};Me.distance=function(e){var t=va({},Pa,e.settings);return va(e.settings,t,{sign:!1,convert:function(e,t,a){return e*Ia[t]/Ia[a]}}),Me.measurement.call(this,e)};var La={min:0,max:100,defaultUnit:"N",units:["N","kp","lbf","pdl"]},Oa={N:1,kp:9.80665,lbf:4.448222,pdl:.138255};Me.force=function(e){var t=va({},La,e.settings);return va(e.settings,t,{sign:!1,convert:function(e,t,a){return e*Oa[t]/Oa[a]}}),Me.measurement.call(this,e)};var Ya={min:0,max:1e3,defaultUnit:"kg",units:["g","kg","oz","lb"],unitNames:{tlong:"t (long)",tshort:"t (short)"}},za={mg:.001,cg:.01,dg:.1,g:1,dag:10,hg:100,kg:1e3,t:1e6,drc:1.7718452,oz:28.3495,lb:453.59237,st:6350.29318,qtr:12700.58636,cwt:50802.34544,tlong:1016046.9088,tshort:907184.74};Me.mass=function(e){var t=va({},Ya,e.settings);return va(e.settings,t,{sign:!1,convert:function(e,t,a){return e*za[t]/za[a]}}),Me.measurement.call(this,e)};var $a={min:0,max:100,defaultUnit:"kph",units:["kph","mph","mps","fps","knot"],unitNames:{kph:"km/h",mph:"mi/h",mps:"m/s",fps:"ft/s",knot:"knot"}},Ra={kph:1,mph:1.60934,mps:3.6,fps:1.09728,knot:1.852};Me.speed=function(e){var t=va({},$a,e.settings);return va(e.settings,t,{sign:!1,convert:function(e,t,a){return e*Ra[t]/Ra[a]}}),Me.measurement.call(this,e)};var ja={min:-20,max:40,defaultUnit:"c",units:["c","k","f","r"],unitNames:{c:"°C",k:"K",f:"°F",r:"°R"}},Wa={c2k:function(e){return e+273.15},c2f:function(e){return 9*e/5+32},c2r:function(e){return 9*(e+273.15)/5},k2c:function(e){return e-273.15},k2f:function(e){return 9*e/5-459.67},k2r:function(e){return 9*e/5},f2c:function(e){return 5*(e-32)/9},f2k:function(e){return 5*(e+459.67)/9},f2r:function(e){return e+459.67},r2c:function(e){return 5*(e-491.67)/9},r2k:function(e){return 5*e/9},r2f:function(e){return e-459.67}};Me.temperature=function(e){var t=va({},ja,e.settings);return va(e.settings,t,{sign:!0,convert:function(e,t,a){return Wa[t+"2"+a](e)}}),Me.measurement.call(this,e)},f("measurement",De),f("distance",De),f("force",De),f("mass",De),f("speed",De),f("temperature",De);function Ja(o,e,t){var r,l,a,s,c,n,i,m,d,u,h,f,p,b,v,x,g,T={},y=1e3,_=this,w=pa(o);function M(e){clearTimeout(u),u=setTimeout(function(){D(!e||"load"!==e.type)},200)}function C(){i&&k(pa(this),!0)}function k(e,t){if(e.length){if(t=_._onItemTap(e,t),(r=e).parent()[0]==o){var a=e.offset().left,s=e[0].offsetLeft,n=e[0].offsetWidth,i=l.offset().left;h&&(s=v-s-n),"a"==b.variant?a<i?f.scroll(h?s+n-c:-s,y,!0):i+c<a+n&&f.scroll(h?s:c-s-n,y,!0):f.scroll((c/2-s-n/2)*(h?-1:1),y,!0)}t&&g("onItemTap",{target:e[0]})}}function S(){var n;_._initMarkup(l),w.find(".mbsc-ripple").remove(),_._$items=w.children(),_._$items.each(function(e){var t,a=pa(this),s=a.attr("data-ref");s=s||Ua++,0===e&&(n=a),r=r||_._getActiveItem(a),t="mbsc-scv-item mbsc-btn-e "+((_._getItemProps(a)||{}).cssClass||""),a.attr("data-ref",s).removeClass(T[s]).addClass(t),T[s]=t}),r=r||n,_._markupReady(l)}function D(e,t){var a=b.itemWidth,s=b.layout;if(_.contWidth=c=l.width(),_._checkResp())return!1;e&&d===c||!c||(d=c,la(s)&&(n=c?c/s:a)<a&&(s="liquid"),a&&("liquid"==s?n=c?c/Math.min(Math.floor(c/a),_._$items.length):a:"fixed"==s&&(n=a)),_._size(c,n),n&&w.children().css("width",n+"px"),_.totalWidth=v=o.offsetWidth,va(f.settings,{contSize:c,maxSnapScroll:!!b.paging&&1,maxScroll:0,minScroll:c<v?c-v:0,snap:b.paging?c:!!p&&(n||".mbsc-scv-item"),elastic:c<v&&(n||c)}),f.refresh(t))}xa.call(this,o,e,!0),_.navigate=function(e,t){k(_._getItem(e),t)},_.next=function(e){if(r){var t=r.next();t.length&&k(r=t,e)}},_.prev=function(e){if(r){var t=r.prev();t.length&&k(r=t,e)}},_.refresh=_.position=function(e){S(),D(!1,e)},_._init=function(){var e;a=pa(b.context),s=pa("body"==b.context?window:b.context),_.__init(),h=b.rtl,p=!(!b.itemWidth||"fixed"==b.layout||void 0!==b.snap)||b.snap,e="mbsc-scv-c mbsc-no-touch mbsc-"+b.theme+" "+(b.cssClass||"")+" "+(b.wrapperClass||"")+(b.baseTheme?" mbsc-"+b.baseTheme:"")+(h?" mbsc-rtl":" mbsc-ltr")+(b.itemWidth?" mbsc-scv-hasw":"")+("body"==b.context?"":" mbsc-scv-ctx")+" "+(_._getContClass()||""),l?(l.attr("class",e),w.off(".mbsc-ripple")):((l=pa('<div class="'+e+'"><div class="mbsc-scv-sc"></div></div>').on("click",".mbsc-scv-item",C).insertAfter(w)).find(".mbsc-scv-sc").append(w),l.find("img").on("load",M),s.on("orientationchange resize",M),m=se(l[0],M,b.zone),f=new lt(l[0],{axis:"X",contSize:0,maxScroll:0,maxSnapScroll:1,minScroll:0,snap:1,elastic:1,rtl:h,mousewheel:b.mousewheel,thresholdX:b.threshold,stopProp:b.stopProp,onStart:function(e){"touchstart"==e.domEvent.type&&(i=!1,x||(x=!0,a.find(".mbsc-no-touch").removeClass("mbsc-no-touch")))},onBtnTap:function(e){i=!0;var t=e.domEvent,a=t.target;"touchend"===t.type&&b.tap&&fa(a,ha(pa(a)),t)},onGestureStart:function(e){g("onGestureStart",e)},onGestureEnd:function(e){g("onGestureEnd",e)},onMove:function(e){g("onMove",e)},onAnimationStart:function(e){g("onAnimationStart",e)},onAnimationEnd:function(e){g("onAnimationEnd",e)}})),w.css("display","").addClass("mbsc-scv").removeClass("mbsc-cloak"),S(),g("onMarkupReady",{target:l[0]}),D()},_._size=ra,_._initMarkup=ra,_._markupReady=ra,_._getContClass=ra,_._getItemProps=ra,_._getActiveItem=ra,_.__init=ra,_.__destroy=ra,_._destroy=function(){_.__destroy(),s.off("orientationchange resize",M),w.removeClass("mbsc-scv").insertAfter(l).find(".mbsc-scv-item").each(function(){var e=pa(this);e.width("").removeClass(T[e.attr("data-ref")])}),l.remove(),f.destroy(),m.detach()},_._getItem=function(e){return"object"!==na(e)&&(e=_._$items.filter('[data-id="'+e+'"]')),pa(e)},_._onItemTap=function(e,t){return void 0===t||t},b=_.settings,g=_.trigger,t||_.init()}var Ua=1;Ja.prototype={_class:"scrollview",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_defaults:{tap:W,stopProp:!1,context:"body",layout:"liquid"}},L.ScrollView=Ja;function qa(a,e,t){var s,n,i,o,r,l,c=pa(a),m=this;function d(){n&&"inline"!=n&&s.find(".mbsc-page").css("padding-"+n,"")}function u(e){e.addClass(r).attr("data-selected","true").attr("aria-selected","true")}function h(e){e.removeClass(r).removeAttr("data-selected").removeAttr("aria-selected")}Ja.call(this,a,e,!0),m.select=function(e){i||h(m._$items.filter(".mbsc-ms-item-sel")),u(m._getItem(e))},m.deselect=function(e){h(m._getItem(e))},m.enable=function(e){m._getItem(e).removeClass("mbsc-disabled").removeAttr("data-disabled").removeAttr("aria-disabled")},m.disable=function(e){m._getItem(e).addClass("mbsc-disabled").attr("data-disabled","true").attr("aria-disabled","true")},m.setBadge=function(e,t){var a;e=m._getItem(e).attr("data-badge",t),(a=pa(".mbsc-ms-badge",e)).length?t?a.html(t):a.remove():t&&e.append('<span class="mbsc-ms-badge">'+t+"</span>")},m._markupReady=function(e){m._hasIcons?e.addClass("mbsc-ms-icons"):e.removeClass("mbsc-ms-icons"),m._hasText?e.addClass("mbsc-ms-txt"):e.removeClass("mbsc-ms-txt"),m.__markupReady(e)},m._size=function(e,t){m.__size(e,t),"inline"!=n&&s.find(".mbsc-page").css("padding-"+n,a.offsetHeight+"px")},m._onItemTap=function(e,t){return!1!==m.__onItemTap(e,t)&&(void 0===t&&(t=!i),o&&t&&!e.hasClass("mbsc-disabled")&&(i?"true"==e.attr("data-selected")?h(e):u(e):(h(m._$items.filter(".mbsc-ms-item-sel")),u(e))),t)},m._getActiveItem=function(e){var t="true"==e.attr("data-selected");if(o&&!i&&t)return e},m._getItemProps=function(e){var t="true"==e.attr("data-selected"),a="true"==e.attr("data-disabled"),s=e.attr("data-icon"),n=e.attr("data-badge");return e.attr("data-role","button").attr("aria-selected",t?"true":"false").attr("aria-disabled",a?"true":"false").find(".mbsc-ms-badge").remove(),n&&e.append('<span class="mbsc-ms-badge">'+n+"</span>"),s&&(m._hasIcons=!0),e.text()&&(m._hasText=!0),{cssClass:"mbsc-ms-item "+(l.itemClass||"")+" "+(t?r:"")+(a?" mbsc-disabled "+(l.disabledClass||""):"")+(s?" mbsc-ms-ic mbsc-ic mbsc-ic-"+s:"")}},m._getContClass=function(){return" mbsc-ms-c mbsc-ms-"+l.variant+" mbsc-ms-"+n+(o?"":" mbsc-ms-nosel")+(m.__getContClass()||"")},m.__init=function(){m.___init(),s=pa(l.context),d(),n=l.display,i="multiple"==l.select,o="off"!=l.select,r=" mbsc-ms-item-sel "+(l.activeClass||""),c.addClass("mbsc-ms mbsc-ms-base "+(l.groupClass||""))},m.__destroy=function(){c.removeClass("mbsc-ms mbsc-ms-base "+(l.groupClass||"")),d(),m.___destroy()},m.__onItemTap=ra,m.__getContClass=ra,m.__markupReady=ra,m.__size=ra,m.___init=ra,m.___destroy=ra,l=m.settings,t||m.init()}qa.prototype={_defaults:va({},Ja.prototype._defaults)};function Ba(e,t){qa.call(this,e,t,!0),this.___init=function(){},this.init()}Ba.prototype={_class:"optionlist",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_defaults:va({},qa.prototype._defaults,{select:"multiple",variant:"a",display:"inline"})},L.Optionlist=Ba,Z.themes.optionlist=Z.themes.navigation,f("optionlist",Ba,!1);function Ka(e,t){var l,c,m,d,u,h=pa(e),s=h.is("ul,ol"),f=this;qa.call(this,e,t,!0),f._initMarkup=function(){l&&l.remove(),c&&h.append(c.children())},f.__size=function(a,s){var n,i=s||72,o=f._$items.length,r=0;u.hide(),"bottom"==d.type&&(h.removeClass("mbsc-scv-liq"),l.remove(),f._$items.remove().each(function(e){var t=pa(this);h.append(t),r+=s||this.offsetWidth||0,Math.round(r+(e<o-1?i:0))>a&&(n=!0,c.append(t.css("width","").addClass("mbsc-fr-btn-e")))}),l.attr("class",m+(d.moreIcon?" mbsc-menu-item-ic mbsc-ms-ic mbsc-ic mbsc-ic-"+d.moreIcon:"")).html(f._hasIcons&&f._hasText?d.moreText:""),n&&h.append(l)),"liquid"==d.layout&&h.addClass("mbsc-scv-liq")},f.__onItemTap=function(e){if(e.hasClass("mbsc-menu-item")&&!1!==f.trigger("onMenuShow",{target:e[0],menu:u}))return u.show(!1,!0),!1},f.__getContClass=function(){return"hamburger"==d.type?" mbsc-ms-hamburger":""},f.__markupReady=function(e){"hamburger"==d.type&&(c.append(f._$items.addClass("mbsc-fr-btn-e")),l.attr("class",m+(d.menuIcon?" mbsc-menu-item-ic mbsc-ms-ic mbsc-ic mbsc-ic-"+d.menuIcon:"")).html(d.menuText||""),h.append(l),d.menuText&&d.menuIcon||e.removeClass("mbsc-ms-icons"),d.menuText?e.addClass("mbsc-ms-txt"):e.removeClass("mbsc-ms-txt"))},f.___init=function(){var a;"tab"==d.type?(d.display=d.display||"top",d.variant=d.variant||"b"):"bottom"==d.type?(d.display=d.display||"bottom",d.variant=d.variant||"a"):"hamburger"==d.type&&(d.display=d.display||"inline",d.variant=d.variant||"a"),m="mbsc-scv-item mbsc-ms-item mbsc-btn-e mbsc-menu-item "+(d.itemClass||""),l||(l=pa(s?"<li></li>":"<div></div>"),c=pa(s?"<ul></ul>":"<div></div>").addClass("mbsc-scv mbsc-ms")),u=new Lt(c[0],{display:"bubble",theme:d.theme,lang:d.lang,context:d.context,buttons:[],anchor:l,onBeforeShow:function(e,t){a=null,t.settings.cssClass="mbsc-wdg mbsc-ms-a mbsc-ms-more"+(f._hasText?"":" mbsc-ms-more-icons")},onBeforeClose:function(){return f.trigger("onMenuHide",{target:a&&a[0],menu:u})},onMarkupReady:function(e,t){f.tap(t._markup.find(".mbsc-fr-c"),function(e){(a=pa(e.target).closest(".mbsc-ms-item")).length&&!a.hasClass("mbsc-disabled")&&(f.navigate(a,!0),u.hide())})}})},f.___destroy=function(){u.destroy(),h.append(f._$items),l.remove()},d=f.settings,f.init()}Ka.prototype={_class:"navigation",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_defaults:va({},qa.prototype._defaults,{type:"bottom",moreText:"More",moreIcon:"material-more-horiz",menuIcon:"material-menu"})},f("nav",L.Navigation=Ka,!1),Me.number=Me.measurement,f("number",De);function Ga(n,e,t){var i,o,r,l,c,m,a,s,d,u,h,f,p,b,v,x,g,T,y,_=pa(n),w=this,M=[],C=[],k={},S={},D={107:"+",109:"-"},V={48:0,49:1,50:2,51:3,52:4,53:5,54:6,55:7,56:8,57:9,96:0,97:1,98:2,99:3,100:4,101:5,102:6,103:7,104:8,105:9};function N(e){var t,a=c.validate.call(n,{values:v.slice(0),variables:k},w)||[],s=a&&a.disabled||[];if(w._isValid=!a.invalid,w._tempValue=c.formatValue.call(n,v.slice(0),k,w),l=v.length,x=a.length||T,w._isVisible){if(pa(".mbsc-np-ph",i).each(function(e){pa(this).html("ltr"==c.fill?l<=e?r:m||v[e]:T-x<=e?e+l<T?r:m||v[e+l-T]:"")}),pa(".mbsc-np-cph",i).each(function(){pa(this).html(k[pa(this).attr("data-var")]||pa(this).attr("data-ph"))}),l===T)for(t=0;t<=9;t++)s.push(t);for(pa(".mbsc-np-btn",i).removeClass(o),t=0;t<s.length;t++)pa('.mbsc-np-btn[data-val="'+s[t]+'"]',i).addClass(o);w._isValid?pa(".mbsc-fr-btn-s .mbsc-fr-btn",i).removeClass(o):pa(".mbsc-fr-btn-s .mbsc-fr-btn",i).addClass(o),w.live&&(w._hasValue=e||w._hasValue,A(e,!1,e),e&&g("onSet",{valueText:w._value}))}}function A(e,t,a,s){t&&N(),s||(y=v.slice(0),S=va({},k),M=C.slice(0),w._value=w._hasValue?w._tempValue:null),e&&(w._isInput&&_.val(w._hasValue&&w._isValid?w._value:""),g("onFill",{valueText:w._hasValue?w._tempValue:"",change:a}),a&&(w._preventChange=!0,_.trigger("change")))}function E(e){var t,a,s=e||[],n=[];for(C=[],k={},t=0;t<s.length;t++)/:/.test(s[t])?(a=s[t].split(":"),k[a[0]]=a[1],C.push(a[0])):(n.push(s[t]),C.push("digit"));return n}function F(e,t){!(l||t||c.allowLeadingZero)||e.hasClass("mbsc-disabled")||e.hasClass("mbsc-np-btn-empty")||l<T&&(C.push("digit"),v.push(t),N(!0))}function H(e){var t,a,s=e.attr("data-val"),n="false"!==e.attr("data-track"),i=e.attr("data-var");if(!e.hasClass("mbsc-disabled")){if(i&&(a=i.split(":"),n&&C.push(a[0]),k[a[0]]=void 0===a[2]?a[1]:k[a[0]]==a[1]?a[2]:a[1]),s.length+l<=x)for(t=0;t<s.length;++t)a=la(s[t])?+s[t]:s[t],(c.allowLeadingZero||l||a)&&(C.push("digit"),v.push(a),l=v.length);N(!0)}}function P(){var e,t,a=C.pop();if(l||"digit"!==a){if("digit"!==a&&k[a])for(delete k[a],t=C.slice(0),C=[],e=0;e<t.length;e++)t[e]!==a&&C.push(t[e]);else v.pop();N(!0)}}function I(){clearInterval(b),p=!1}function L(e){if(ga(e,this)){if("keydown"==e.type&&32!=e.keyCode)return;!function(e){p=!0,a=ua(e,"X"),s=ua(e,"Y"),clearInterval(b),clearTimeout(b),P(),b=setInterval(function(){P()},150)}(e),"mousedown"==e.type&&pa(document).on("mousemove",O).on("mouseup",Y)}}function O(e){p&&(d=ua(e,"X"),u=ua(e,"Y"),h=d-a,f=u-s,(7<Math.abs(h)||7<Math.abs(f))&&I())}function Y(e){p&&(e.preventDefault(),I(),"mouseup"==e.type&&pa(document).off("mousemove",O).off("mouseup",Y))}fe.call(this,n,e,!0),w.setVal=w._setVal=function(e,t,a,s){w._hasValue=null!=e,v=E(pa.isArray(e)?e.slice(0):c.parseValue.call(n,e,w)),A(t,!0,void 0===a?t:a,s)},w.getVal=w._getVal=function(e){return w._hasValue||e?w[e?"_tempValue":"_value"]:null},w.setArrayVal=w.setVal,w.getArrayVal=function(e){return e?v.slice(0):w._hasValue?y.slice(0):null},w._readValue=function(){var e=_.val()||"";""!==e&&(w._hasValue=!0),m?(k={},C=[],v=[]):(k=w._hasValue?S:{},C=w._hasValue?M:[],v=w._hasValue&&y?y.slice(0):E(c.parseValue.call(n,e,w)),A(!1,!0))},w._fillValue=function(){A(w._hasValue=!0,!1,!0)},w._generateContent=function(){var e,t,a,s=1,n="";for(n+='<div class="mbsc-np-hdr"><div role="button" tabindex="0" aria-label="'+c.deleteText+'" class="mbsc-np-del mbsc-fr-btn-e mbsc-ic mbsc-ic-'+c.deleteIcon+'"></div><div class="mbsc-np-dsp">',n+=c.template.replace(/d/g,'<span class="mbsc-np-ph">'+r+"</span>").replace(/&#100;/g,"d").replace(/{([a-zA-Z0-9]*)\:?([a-zA-Z0-9\-\_]*)}/g,'<span class="mbsc-np-cph" data-var="$1" data-ph="$2">$2</span>'),n+="</div></div>",n+='<div class="mbsc-np-tbl-c mbsc-w-p"><div class="mbsc-np-tbl">',e=0;e<4;e++){for(n+='<div class="mbsc-np-row">',t=0;t<3;t++)10==(a=s)||12==s?a="":11==s&&(a=0),""===a?10==s&&c.leftKey?n+='<div role="button" tabindex="0" class="mbsc-np-btn mbsc-np-btn-custom mbsc-fr-btn-e" '+(c.leftKey.variable?'data-var="'+c.leftKey.variable+'"':"")+' data-val="'+(c.leftKey.value||"")+'" '+(void 0!==c.leftKey.track?' data-track="'+c.leftKey.track+'"':"")+">"+c.leftKey.text+"</div>":12==s&&c.rightKey?n+='<div role="button" tabindex="0" class="mbsc-np-btn mbsc-np-btn-custom mbsc-fr-btn-e" '+(c.rightKey.variable?'data-var="'+c.rightKey.variable+'"':"")+' data-val="'+(c.rightKey.value||"")+'" '+(void 0!==c.rightKey.track?' data-track="'+c.rightKey.track+'"':"")+" >"+c.rightKey.text+"</div>":n+='<div class="mbsc-np-btn mbsc-np-btn-empty"></div>':n+='<div tabindex="0" role="button" class="mbsc-np-btn mbsc-fr-btn-e" data-val="'+a+'">'+a+"</div>",s++;n+="</div>"}return n+="</div></div>"},w._markupReady=function(){i=w._markup,N()},w._attachEvents=function(a){a.on("keydown",function(e){var t;void 0!==D[e.keyCode]?(t=pa('.mbsc-np-btn[data-var="sign:-:"]',a)).length&&(k.sign=107==e.keyCode?"-":"",H(t)):void 0!==V[e.keyCode]?F(pa('.mbsc-np-btn[data-val="'+V[e.keyCode]+'"]',a),V[e.keyCode]):8==e.keyCode&&(e.preventDefault(),P())}),w.tap(pa(".mbsc-np-btn",a),function(){var e=pa(this);e.hasClass("mbsc-np-btn-custom")?H(e):F(e,+e.attr("data-val"))},!1,30,!0),pa(".mbsc-np-del",a).on("touchstart mousedown keydown",L).on("touchmove mousemove",O).on("touchend mouseup keyup",Y)},w.__init=function(){(c=w.settings).template=c.template.replace(/\\d/,"&#100;"),r=c.placeholder,T=(c.template.match(/d/g)||[]).length,o="mbsc-disabled "+(c.disabledClass||""),m=c.mask,g=w.trigger,m&&_.is("input")&&_.attr("type","password")},w._indexOf=function(e,t){var a;for(a=0;a<e.length;++a)if(e[a].toString()===t.toString())return a;return-1},t||w.init()}var Xa={};Ga.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_class:"numpad",_presets:Xa,_defaults:va({},fe.prototype._defaults,{template:"dd.dd",placeholder:"0",deleteIcon:"backspace",allowLeadingZero:!1,headerText:!1,fill:"rtl",compClass:"mbsc-np",deleteText:"Delete",decimalSeparator:".",thousandsSeparator:",",validate:ra,parseValue:ra,formatValue:function(e,t,a){var s,n=1,i=a.settings,o=i.placeholder,r=i.template,l=e.length,c=r.length,m="";for(s=0;s<c;s++)"d"==r[c-s-1]?(m=n<=l?e[l-n]+m:o+m,n++):m=r[c-s-1]+m;return pa.each(t,function(e,t){m=m.replace("{"+e+"}",t)}),pa("<div>"+m+"</div>").text()}})},L.Numpad=Ga,Z.themes.numpad=Z.themes.frame;var Za={min:0,max:99.99,scale:2,prefix:"",suffix:"",returnAffix:!1};function Qa(e){for(var t=0,a=1,s=0;e.length;)3<t?a=3600:1<t&&(a=60),s+=e.pop()*a*(t%2?10:1),t++;return s}Xa.decimal=function(i){function o(e,t){for(var a,s=e.slice(0),n=0;s.length;)n=10*n+s.shift();for(a=0;a<c;a++)n/=10;return t?-1*n:n}function r(e){var t=o(e).toFixed(c).split(".");return t[0].replace(/\B(?=(\d{3})+(?!\d))/g,l.thousandsSeparator)+(t[1]?l.decimalSeparator+t[1]:"")}var e=va({},i.settings),l=va(i.settings,Za,e),c=l.scale,m=+l.min.toFixed(c),d=+l.max.toFixed(c),t=m<0,u=new RegExp(l.thousandsSeparator,"g");return i.setVal=function(e,t,a,s){return i._setVal(ue(e,m,d),t,a,s)},i.getVal=function(e){var t=i._getVal(e),a=(t+"").replace(u,"").replace(l.decimalSeparator,".");return la(a)?+a:t},{template:(t?"{sign}":"")+l.prefix.replace(/d/g,"\\d")+Array((Math.floor(Math.max(d,Math.abs(m)))+"").length+1).join("d")+(c?"."+Array(c+1).join("d"):"")+l.suffix.replace(/d/g,"\\d"),leftKey:t?{text:"-/+",variable:"sign:-:",track:!1}:void 0,parseValue:function(e){var t,a,s=e||l.defaultValue,n=[];if(s&&(a=(s=(s+"").replace(u,"").replace(l.decimalSeparator,".")).match(/\d+\.?\d*/g)))for(a=(+a[0]).toFixed(c),t=0;t<a.length;t++)"."!=a[t]&&(+a[t]?n.push(+a[t]):n.length&&n.push(0));return e<0&&n.push("sign:-"),n},formatValue:function(e,t){var a=r(e);return(o(e,t&&"-"==t.sign)<0?"-":"")+(l.returnAffix?l.prefix+a+l.suffix:a)},validate:function(e){var t=e.values,a=r(t),s=o(t,e.variables&&"-"==e.variables.sign),n=[];return t.length||l.allowLeadingZero||n.push(0),i.isVisible()&&pa(".mbsc-np-dsp",i._markup).html((e.variables.sign||"")+l.prefix+a+l.suffix),{disabled:n,invalid:d<s||s<m||!!l.invalid&&-1!=i._indexOf(l.invalid,s)}}}};var es=["h","m","s"],ts={min:0,max:362439,defaultValue:0,hourTextShort:"h",minuteTextShort:"m",secTextShort:"s"};Xa.timespan=function(n){var e=va({},n.settings),i=va(n.settings,ts,e),o={h:i.hourTextShort.replace(/d/g,"\\d"),m:i.minuteTextShort.replace(/d/g,"\\d"),s:i.secTextShort.replace(/d/g,"\\d")},t='d<span class="mbsc-np-sup mbsc-np-time">'+o.s+"</span>";function r(a){var s,n="",i=3600;return pa(es).each(function(e,t){s=Math.floor(a/i),a-=s*i,i/=60,(0<s||"s"==t&&!n)&&(n=n+(n?" ":"")+s+o[t])}),n}return 9<i.max&&(t="d"+t),99<i.max&&(t='<span class="mbsc-np-ts-m">'+(639<i.max?"d":"")+'d</span><span class="mbsc-np-sup mbsc-np-time">'+o.m+"</span>"+t),6039<i.max&&(t='<span class="mbsc-np-ts-h">'+(38439<i.max?"d":"")+'d</span><span class="mbsc-np-sup mbsc-np-time">'+o.h+"</span>"+t),n.setVal=function(e,t,a,s){return la(e)&&(e=r(e)),n._setVal(e,t,a,s)},n.getVal=function(e){return n._hasValue||e?Qa(n.getArrayVal(e)):null},{template:t,parseValue:function(e){var a,s=e||r(i.defaultValue),n=[];return s&&pa(es).each(function(e,t){(a=new RegExp("(\\d+)"+o[t],"gi").exec(s))?9<(a=+a[1])?(n.push(Math.floor(a/10)),n.push(a%10)):(n.length&&n.push(0),(a||n.length)&&n.push(a)):n.length&&(n.push(0),n.push(0))}),n},formatValue:function(e){return r(Qa(e))},validate:function(e){var t=e.values,a=Qa(t.slice(0)),s=[];return t.length||s.push(0),{disabled:s,invalid:a>i.max||a<i.min||!!i.invalid&&-1!=n._indexOf(i.invalid,+a)}}}};var as={timeFormat:"hh:ii A",amText:"am",pmText:"pm"};Xa.time=function(n){var e=va({},n.settings),h=va(n.settings,as,e),f=h.timeFormat.split(":"),p=h.timeFormat.match(/a/i),i=p?"a"==p[0]?h.amText:h.amText.toUpperCase():"",o=p?"a"==p[0]?h.pmText:h.pmText.toUpperCase():"",b=0,v=h.min?""+h.min.getHours():"",x=h.max?""+h.max.getHours():"",g=h.min?""+(h.min.getMinutes()<10?"0"+h.min.getMinutes():h.min.getMinutes()):"",T=h.max?""+(h.max.getMinutes()<10?"0"+h.max.getMinutes():h.max.getMinutes()):"",y=h.min?""+(h.min.getSeconds()<10?"0"+h.min.getSeconds():h.min.getSeconds()):"",_=h.max?""+(h.max.getSeconds()<10?"0"+h.max.getSeconds():h.max.getSeconds()):"";
/* eslint-disable */function r(e,t){var a,s="";for(a=0;a<e.length;++a)s+=e[a]+(a%2==(e.length%2==1?0:1)&&a!=e.length-1?":":"");return pa.each(t,function(e,t){s+=" "+t}),s}return h.min&&h.min.setFullYear(2014,7,20),h.max&&h.max.setFullYear(2014,7,20),{placeholder:"-",allowLeadingZero:!0,template:(3==f.length?"dd:dd:dd":2==f.length?"dd:dd":"dd")+(p?'<span class="mbsc-np-sup">{ampm:--}</span>':""),leftKey:p?{text:i,variable:"ampm:"+i,value:"00"}:{text:":00",value:"00"},rightKey:p?{text:o,variable:"ampm:"+o,value:"00"}:{text:":30",value:"30"},parseValue:function(e){var t,a,s=e||h.defaultValue,n=[];if(s){if(a=(s+="").match(/\d/g))for(t=0;t<a.length;t++)n.push(+a[t]);p&&n.push("ampm:"+(s.match(new RegExp(h.pmText,"gi"))?o:i))}return n},formatValue:function(e,t){return r(e,t)},validate:function(e){var t=e.values,a=r(t,e.variables),s=3<=t.length?new Date(2014,7,20,""+t[0]+(t.length%2==0?t[1]:""),""+t[t.length%2==0?2:1]+t[t.length%2==0?3:2]):"";return{disabled:function(e){var t,a,s,n,i,o,r,l,c,m,d=[],u=2*f.length;if(b=u,e.length||(p&&(d.push(0),d.push(h.leftKey.value)),d.push(h.rightKey.value)),!p&&(u-e.length<2||1!=e[0]&&(2<e[0]||3<e[1])&&u-e.length<=2)&&(d.push("30"),d.push("00")),(p?1<e[0]||2<e[1]:1!=e[0]&&(2<e[0]||3<e[1]))&&e[0]&&(e.unshift(0),b=u-1),e.length==u)for(t=0;t<=9;++t)d.push(t);else if(1==e.length&&p&&1==e[0]||e.length&&e.length%2==0||!p&&2==e[0]&&3<e[1]&&e.length%2==1)for(t=6;t<=9;++t)d.push(t);if(c=void 0!==e[1]?""+e[0]+e[1]:"",m=+T==+(void 0!==e[3]?""+e[2]+e[3]:""),h.invalid)for(t=0;t<h.invalid.length;++t)if(o=h.invalid[t].getHours(),r=h.invalid[t].getMinutes(),l=h.invalid[t].getSeconds(),o==+c){if(2==f.length&&(r<10?0:+(""+r)[0])==+e[2]){d.push(r<10?r:+(""+r)[1]);break}if((l<10?0:+(""+l)[0])==+e[4]){d.push(l<10?l:+(""+l)[1]);break}}if(h.min||h.max){if(i=(s=+x==+c)&&m,n=(a=+v==+c)&&m,0===e.length){for(t=p?2:19<v?v[0]:3;t<=(1==v[0]?9:v[0]-1);++t)d.push(t);if(10<=v&&(d.push(0),2==v[0]))for(t=3;t<=9;++t)d.push(t);if(x&&x<10||v&&10<=v)for(t=x&&x<10?+x[0]+1:0;t<(v&&10<=v?v[0]:10);++t)d.push(t)}if(1==e.length){if(0===e[0])for(t=0;t<v[0];++t)d.push(t);if(v&&0!==e[0]&&(p?1==e[0]:2==e[0]))for(t=p?3:4;t<=9;++t)d.push(t);if(e[0]==v[0])for(t=0;t<v[1];++t)d.push(t);if(e[0]==x[0]&&!p)for(t=+x[1]+1;t<=9;++t)d.push(t)}if(2==e.length&&(a||s))for(t=s?+T[0]+1:0;t<(a?+g[0]:10);++t)d.push(t);if(3==e.length&&(s&&e[2]==T[0]||a&&e[2]==g[0]))for(t=s&&e[2]==T[0]?+T[1]+1:0;t<(a&&e[2]==g[0]?+g[1]:10);++t)d.push(t);if(4==e.length&&(n||i))for(t=i?+_[0]+1:0;t<(n?+y[0]:10);++t)d.push(t);if(5==e.length&&(n&&e[4]==y[0]||i&&e[4]==_[0]))for(t=i&&e[4]==_[0]?+_[1]+1:0;t<(n&&e[4]==y[0]?+y[1]:10);++t)d.push(t)}return d}(t),length:b,invalid:(p?!new RegExp("^(0?[1-9]|1[012])(:[0-5]\\d)?(:[0-5][0-9]) (?:"+h.amText+"|"+h.pmText+")$","i").test(a):!/^([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])?$/.test(a))||!!h.invalid&&-1!=n._indexOf(h.invalid,s)||!((!h.min||h.min<=s)&&(!h.max||s<=h.max))}}}};var ss={dateOrder:"mdy",dateFormat:"mm/dd/yy",delimiter:"/"};f("numpad",Ga,!(Xa.date=function(s){var f,p,b,e,i=[],t=va({},s.settings),v=va(s.settings,he,ss,t),a=v.dateOrder,x=v.min?""+(v.getMonth(v.min)+1):0,g=v.max?""+(v.getMonth(v.max)+1):0,T=v.min?""+v.getDay(v.min):0,y=v.max?""+v.getDay(v.max):0,_=v.min?""+v.getYear(v.min):0,w=v.max?""+v.getYear(v.max):0;for(a=(a=(a=a.replace(/y+/gi,"yyyy")).replace(/m+/gi,"mm")).replace(/d+/gi,"dd"),f=a.toUpperCase().indexOf("Y"),p=a.toUpperCase().indexOf("M"),b=a.toUpperCase().indexOf("D"),a="",i.push({val:f,n:"yyyy"},{val:p,n:"mm"},{val:b,n:"dd"}),i.sort(function(e,t){return e.val-t.val}),pa.each(i,function(e,t){a+=t.n}),f=a.indexOf("y"),p=a.indexOf("m"),b=a.indexOf("d"),a="",e=0;e<8;++e)a+="d",e+1!=f&&e+1!=p&&e+1!=b||(a+=v.delimiter);function n(e){return new Date(+(""+e[f]+e[f+1]+e[f+2]+e[f+3]),""+e[p]+e[p+1]-1,+(""+e[b]+e[b+1]))}return s.getVal=function(e){return s._hasValue||e?n(s.getArrayVal(e)):null},{placeholder:"-",fill:"ltr",allowLeadingZero:!0,template:a,parseValue:function(e){var t,a=[],s=e||v.defaultValue,n=le(v.dateFormat,s,v);if(s)for(t=0;t<i.length;++t)a=/m/i.test(i[t].n)?a.concat(((v.getMonth(n)<9?"0":"")+(v.getMonth(n)+1)).split("")):/d/i.test(i[t].n)?a.concat(((v.getDay(n)<10?"0":"")+v.getDay(n)).split("")):a.concat((v.getYear(n)+"").split(""));return a},formatValue:function(e){return re(v.dateFormat,n(e),v)},validate:function(e){var t=e.values,a=n(t);return{disabled:function(e){var t,a,s,n,i,o,r=[],l=void 0!==e[f+3]?""+e[f]+e[f+1]+e[f+2]+e[f+3]:"",c=void 0!==e[p+1]?""+e[p]+e[p+1]:"",m=void 0!==e[b+1]?""+e[b]+e[b+1]:"",d=""+v.getMaxDayOfMonth(l||2012,c-1||0),u=_===l&&+x==+c,h=w===l&&+g==+c;if(v.invalid)for(t=0;t<v.invalid.length;++t){if(s=v.getYear(v.invalid[t]),n=v.getMonth(v.invalid[t]),i=v.getDay(v.invalid[t]),s==+l&&n+1==+c&&(i<10?0:+(""+i)[0])==+e[b]){r.push(i<10?i:+(""+i)[1]);break}if(n+1==+c&&i==+m&&(""+s).substring(0,3)==""+e[f]+e[f+1]+e[f+2]){r.push((""+s)[3]);break}if(s==+l&&i==+m&&(n<10?0:+(""+(n+1))[0])==+e[p]){r.push(n<10?n:+(""+(n+1))[1]);break}}if("31"!=m||e.length!=p&&e.length!=p+1||(1!=e[p]?r.push(2,4,6,9,11):r.push(1)),"30"==m&&0===e[p]&&e.length<=p+1&&r.push(2),e.length==p){for(t=w===l&&+g<10?1:2;t<=9;++t)r.push(t);_===l&&10<=+x&&r.push(0)}if(e.length==p+1){if(1==e[p]){for(t=w===l?+g[1]+1:3;t<=9;++t)r.push(t);if(_==l)for(t=0;t<+x[1];++t)r.push(t)}if(0===e[p]&&(r.push(0),w===l||_===l))for(t=w===l?+y<+m?+g:+g+1:0;t<=(_===l?x-1:9);++t)r.push(t)}if(e.length==b){for(t=h?1+(10<+y?+y[0]:0):+d[0]+1;t<=9;++t)r.push(t);if(u)for(t=0;t<(+T<10?0:T[0]);++t)r.push(t)}if(e.length==b+1){if(3<=e[b]||"02"==c)for(t=+d[1]+1;t<=9;++t)r.push(t);if(h&&+y[0]==e[b])for(t=+y[1]+1;t<=9;++t)r.push(t);if(u&&T[0]==e[b])for(t=0;t<+T[1];++t)r.push(t);if(0===e[b]&&(r.push(0),h||u))for(t=h?+y+1:1;t<=(u?T-1:9);++t)r.push(t)}if(void 0!==e[f+2]&&"02"==c&&"29"==m)for(a=+(""+e[f]+e[f+1]+e[f+2]+0);a<=+(""+e[f]+e[f+1]+e[f+2]+9);++a)r.push((o=a)%4==0&&o%100!=0||o%400==0?"":a%10);if(e.length==f){if(v.min)for(t=0;t<+_[0];++t)r.push(t);if(v.max)for(t=+w[0]+1;t<=9;++t)r.push(t);r.push(0)}if(v.min||v.max)for(a=1;a<4;++a)if(e.length==f+a){if(e[f+a-1]==+_[a-1]&&(3!=a||e[f+a-2]==+_[a-2]))for(t=0;t<+_[a]+(3==a&&e[p+1]&&+c<+x?1:0);++t)r.push(t);if(e[f+a-1]==+w[a-1]&&(3!=a||e[f+a-2]==+w[a-2]))for(t=+w[a]+(3==a&&+g<+c?0:1);t<=9;++t)r.push(t)}return r}(t),invalid:!("Invalid Date"!=a&&(!v.min||v.min<=a)&&(!v.max||a<=v.max))||!!v.invalid&&-1!=s._indexOf(v.invalid,a)}}}}));var ns={autoCorrect:!0,showSelector:!0,minRange:1,rangeTap:!0};Me.range=function(l){function a(e,t){e&&(e.setFullYear(t.getFullYear()),e.setMonth(t.getMonth()),e.setDate(t.getDate()))}function s(e,t){var a=l._order,s=new Date(e);return void 0===a.h&&s.setHours(t?23:0),void 0===a.i&&s.setMinutes(t?59:0),void 0===a.s&&s.setSeconds(t?59:0),s.setMilliseconds(t?999:0),s}function t(e,t){return new Date(e.getFullYear(),e.getMonth(),e.getDate()+t)}function n(e){p?(_-T>N.maxRange-1&&(e?T=new Date(Math.max(x,_-N.maxRange+1)):_=new Date(Math.min(v,+T+N.maxRange-1))),_-T<N.minRange-1&&(e?T=new Date(Math.max(x,_-N.minRange+1)):_=new Date(Math.min(v,+T+N.minRange-1)))):(Math.ceil((_-T)/F)>P&&(e?T=s(Math.max(x,t(_,1-P)),!1):_=s(Math.min(v,t(T,P-1)),!0)),Math.ceil((_-T)/F)<H&&(e?T=s(Math.max(x,t(_,1-H)),!1):_=s(Math.min(v,t(T,H-1)),!0)))}function i(e,t){var a=!0;return e&&T&&_&&(n(D),n(!D)),T&&_||(a=!1),t&&r(),a}function o(){C&&u&&(pa(".mbsc-range-btn",u).removeClass(L).removeAttr("aria-checked"),function(e){e.addClass(L).attr("aria-checked","true")}(pa(".mbsc-range-btn",u).eq(D)))}function r(){var e,t,a,s,n,i=0,o=E||!D?" mbsc-cal-day-hl mbsc-cal-sel-start":" mbsc-cal-sel-start",r=E||D?" mbsc-cal-day-hl mbsc-cal-sel-end":" mbsc-cal-sel-end";if(l.startVal=T?re(f,T,N):"",l.endVal=_?re(f,_,N):"",u&&(pa(".mbsc-range-btn-v-start",u).html(l.startVal||"&nbsp;"),pa(".mbsc-range-btn-v-end",u).html(l.endVal||"&nbsp;"),e=T?new Date(T):null,a=_?new Date(_):null,!e&&a&&(e=new Date(a)),!a&&e&&(a=new Date(e)),n=D?a:e,pa(".mbsc-cal-day-picker .mbsc-cal-day-hl",u).removeClass(O),pa(".mbsc-cal-day-picker .mbsc-selected",u).removeClass("mbsc-cal-sel-start mbsc-cal-sel-end "+L).removeAttr("aria-selected"),e&&a))for(t=e.setHours(0,0,0,0),s=a.setHours(0,0,0,0);e<=a&&i<126;)pa('.mbsc-cal-day[data-full="'+n.getFullYear()+"-"+(n.getMonth()+1)+"-"+n.getDate()+'"]',u).addClass(L+" "+(n.getTime()===t?o:"")+(n.getTime()===s?r:"")).attr("aria-selected","true"),n.setDate(n.getDate()+(D?-1:1)),n.setHours(0,0,0,0),i++}function c(e,t){return{h:e?e.getHours():t?23:0,i:e?e.getMinutes():t?59:0,s:e?e.getSeconds():t?59:0}}function m(){T&&(b=!0,l.setDate(T,!1,0,!0),T=l.getDate(!0)),_&&(b=!0,l.setDate(_,!1,0,!0),_=l.getDate(!0))}var d,u,h,f,p,b,v,x,g,T,y,_,w,M,C,k=l._startDate,S=l._endDate,D=0,e=new Date,V=va({},l.settings),N=va(l.settings,ns,V),A=N.anchor,E=N.rangeTap,F=864e5,H=Math.max(1,Math.ceil(N.minRange/F)),P=Math.max(1,Math.ceil(N.maxRange/F)),I="mbsc-disabled "+(N.disabledClass||""),L="mbsc-selected "+(N.selectedClass||""),O="mbsc-cal-day-hl",Y=null===N.defaultValue?[]:N.defaultValue||[new Date(e.setHours(0,0,0,0)),new Date(e.getFullYear(),e.getMonth(),e.getDate()+6,23,59,59,999)];return E&&(N.tabs=!0),d=Ve.call(this,l),f=l._format,p=/time/i.test(N.controls.join(",")),M="time"===N.controls.join(""),C=N.showSelector,v=N.max?s(at(N.max,f,N),!0):1/0,x=N.min?s(at(N.min,f,N),!1):-1/0,Y[0]=at(Y[0],f,N,N.isoParts),Y[1]=at(Y[1],f,N,N.isoParts),N.startInput&&l.attachShow(pa(N.startInput),function(){D=0,N.anchor=A||pa(N.startInput)}),N.endInput&&l.attachShow(pa(N.endInput),function(){D=1,N.anchor=A||pa(N.endInput)}),l._getDayProps=function(e,t){var a=T?new Date(T.getFullYear(),T.getMonth(),T.getDate()):null,s=_?new Date(_.getFullYear(),_.getMonth(),_.getDate()):null;return{selected:a&&s&&a<=e&&e<=_,cssClass:t.cssClass+" "+((E||!D)&&a&&a.getTime()===e.getTime()||(E||D)&&s&&s.getTime()===e.getTime()?O:"")+(a&&a.getTime()===e.getTime()?" mbsc-cal-sel-start":"")+(s&&s.getTime()===e.getTime()?" mbsc-cal-sel-end":"")}},l.setVal=function(e,t,a,s,n){var i,o=e||[];T=at(o[0],f,N,N.isoParts),_=at(o[1],f,N,N.isoParts),m(),l.startVal=T?re(f,T,N):"",l.endVal=_?re(f,_,N):"",i=d.parseValue(D?_:T,l),s||(l._startDate=k=T,l._endDate=S=_),g=!0,l._setVal(i,t,a,s,n)},l.getVal=function(e){return e?[ce(T,N,f),ce(_,N,f)]:l._hasValue?[ce(k,N,f),ce(S,N,f)]:null},l.setActiveDate=function(e){var t;D="start"==e?0:1,t="start"==e?T:_,l.isVisible()&&(o(),E||(pa(".mbsc-cal-table .mbsc-cal-day-hl",u).removeClass(O),t&&pa('.mbsc-cal-day[data-full="'+t.getFullYear()+"-"+(t.getMonth()+1)+"-"+t.getDate()+'"]',u).addClass(O)),t&&(b=!0,l.setDate(t,!1,1e3,!0)))},l.getValue=l.getVal,va({},d,{highlight:!1,outerMonthChange:!1,formatValue:function(){return l.startVal+(N.endInput?"":l.endVal?" - "+l.endVal:"")},parseValue:function(e){var t=e?e.split(" - "):[],a=N.startInput?pa(N.startInput).val():t[0],s=N.endInput?pa(N.endInput).val():t[1];return N.defaultValue=Y[1],S=s?le(f,s,N):Y[1],N.defaultValue=Y[0],k=a?le(f,a,N):Y[2],N.defaultValue=Y[D],l.startVal=k?re(f,k,N):"",l.endVal=S?re(f,S,N):"",l._startDate=k,l._endDate=S,d.parseValue(D?S:k,l)},onFill:function(e){!function(e){l._startDate=k=T,l._endDate=S=_,N.startInput&&(pa(N.startInput).val(l.startVal),e&&pa(N.startInput).trigger("change")),N.endInput&&(pa(N.endInput).val(l.endVal),e&&pa(N.endInput).trigger("change"))}(e.change)},onBeforeClose:function(e){if("set"===e.button&&!i(!0,!0))return l.setActiveDate(D?"start":"end"),!1},onHide:function(){d.onHide.call(l),D=0,u=null,N.anchor=A},onClear:function(){E&&(D=0)},onBeforeShow:function(){T=k||Y[0],_=S||Y[1],y=c(T,0),w=c(_,1),N.counter&&(N.headerText=function(){var e=T&&_?Math.max(1,Math.round((new Date(_).setHours(0,0,0,0)-new Date(T).setHours(0,0,0,0))/864e5)+1):0;return(1<e&&N.selectedPluralText||N.selectedText).replace(/{count}/,e)}),g=!0},onMarkupReady:function(e){var t;m(),(D&&_||!D&&T)&&(b=!0,l.setDate(D?_:T,!1,0,!0)),r(),d.onMarkupReady.call(this,e),(u=pa(e.target)).addClass("mbsc-range"),C&&(t='<div class="mbsc-range-btn-t" role="radiogroup"><div class="mbsc-range-btn-c mbsc-range-btn-start"><div role="radio" data-select="start" class="mbsc-fr-btn-e mbsc-fr-btn-nhl mbsc-range-btn">'+N.fromText+'<div class="mbsc-range-btn-v mbsc-range-btn-v-start">'+(l.startVal||"&nbsp;")+'</div></div></div><div class="mbsc-range-btn-c mbsc-range-btn-end"><div role="radio" data-select="end" class="mbsc-fr-btn-e mbsc-fr-btn-nhl mbsc-range-btn">'+N.toText+'<div class="mbsc-range-btn-v mbsc-range-btn-v-end">'+(l.endVal||"&nbsp;")+"</div></div></div></div>",N.headerText?pa(".mbsc-fr-hdr",u).after(t):pa(".mbsc-fr-w",u).prepend(t),o()),pa(".mbsc-range-btn",u).on("touchstart click",function(e){ga(e,this)&&(l._showDayPicker(),l.setActiveDate(pa(this).attr("data-select")))})},onDayChange:function(e){e.active=D?"end":"start",h=!0},onSetDate:function(e){var t;b||(t=s(e.date,D),g&&!h||(E&&h&&(1==D&&t<T&&(D=0),D?t.setHours(w.h,w.i,w.s,999):t.setHours(y.h,y.i,y.s,0)),D?(_=new Date(t),w=c(_)):(T=new Date(t),y=c(T)),M&&N.autoCorrect&&(a(T,t),a(_,t)),E&&h&&!D&&(_=null))),M&&!N.autoCorrect&&_<T&&(_=new Date(_.setDate(_.getDate()+1))),l._isValid=i(g||h||N.autoCorrect,!b),e.active=D?"end":"start",!b&&E&&(h&&(D=D?0:1),o()),l.isVisible()&&(l._isValid?pa(".mbsc-fr-btn-s .mbsc-fr-btn",l._markup).removeClass(I):pa(".mbsc-fr-btn-s .mbsc-fr-btn",l._markup).addClass(I)),b=g=h=!1},onTabChange:function(e){"calendar"!=e.tab&&l.setDate(D?_:T,!1,1e3,!0),i(!0,!0)}})},f("range",De),f("scroller",De,!1),f("scrollview",Ja,!1);var is={inputClass:"",rtl:!1,showInput:!0,groupLabel:"Groups",dataHtml:"html",dataText:"text",dataValue:"value",dataGroup:"group",dataDisabled:"disabled",filterPlaceholderText:"Type to filter",filterEmptyText:"No results",filterClearIcon:"material-close"};Me.select=function(r,e){var l,h,c,m,a,f,s,p,d,u,n,b,i,v,o,t="",x={},g=1e3,T=this,y=pa(T),_=va({},r.settings),w=va(r.settings,is,_),M=pa('<div class="mbsc-sel-empty">'+w.filterEmptyText+"</div>"),C=w.readonly,k={},S=w.layout||(/top|bottom|inline/.test(w.display)||w.filter?"liquid":""),D="liquid"==S||!w.touchUi,V=la(w.select)?w.select:"multiple"==w.select||y.prop("multiple"),N=V||!(!w.filter&&!w.tapSelect)&&1,A=this.id+"_dummy",E=pa('label[for="'+this.id+'"]').attr("for",A),F=void 0!==w.label?w.label:E.length?E.text():y.attr("name"),H=w.group,P=!!w.data,I=P?!!w.group:pa("optgroup",y).length,L=I&&H&&!1!==H.groupWheel,O=I&&H&&L&&!0===H.clustered,Y=I&&(!H||!1!==H.header&&!O),z=y.val()||(V?[]:[""]),$=[];function R(a){var s,n,i,o,r,l,c=0,m=0,d={};if(k={},p={},b=[],f=[],$.length=0,P)pa.each(h,function(e,t){r=t[w.dataText]+"",n=t[w.dataHtml],l=t[w.dataValue],i=t[w.dataGroup],o={value:l,html:n,text:r,index:e,cssClass:Y?"mbsc-sel-gr-itm":""},k[l]=o,a&&!G(r,a)||(b.push(o),I&&(void 0===d[i]?(s={text:i,value:m,options:[],index:m},p[m]=s,d[i]=m,f.push(s),m++):s=p[d[i]],O&&(o.index=s.options.length),o.group=d[i],s.options.push(o)),t[w.dataDisabled]&&$.push(l))});else if(I){var u=!0;pa("optgroup",y).each(function(t){p[t]={text:this.label,value:t,options:[],index:t},u=!0,pa("option",this).each(function(e){o={value:this.value,text:this.text,index:O?e:c++,group:t,cssClass:Y?"mbsc-sel-gr-itm":""},k[this.value]=o,a&&!G(this.text,a)||(u&&(f.push(p[t]),u=!1),b.push(o),p[t].options.push(o),this.disabled&&$.push(this.value))})})}else pa("option",y).each(function(e){o={value:this.value,text:this.text,index:e},k[this.value]=o,a&&!G(this.text,a)||(b.push(o),this.disabled&&$.push(this.value))});t=w.defaultValue?w.defaultValue:b.length?b[0].value:"",Y&&(b=[],c=0,pa.each(p,function(e,t){t.options.length&&(l="__group"+e,o={text:t.text,value:l,group:e,index:c++,cssClass:"mbsc-sel-gr"},k[l]=o,b.push(o),$.push(o.value),pa.each(t.options,function(e,t){t.index=c++,b.push(t)}))})),M&&(b.length?M.removeClass("mbsc-sel-empty-v"):M.addClass("mbsc-sel-empty-v"))}function j(e,t,a,s,n){var i,o=[];for(i=0;i<e.length;i++)o.push({value:e[i].value,display:e[i].html||e[i].text,cssClass:e[i].cssClass});return{circular:!1,multiple:t&&!s?1:s,cssClass:(t&&!s?"mbsc-sel-one":"")+" "+n,data:o,label:a}}function W(){return j(O&&p[a]?p[a].options:b,N,F,V,"")}function J(){var e=[[]];return L&&(s=j(f,N,w.groupLabel,!1,"mbsc-sel-gr-whl"),D?e[0][d]=s:e[d]=[s]),i=W(),D?e[0][v]=i:e[v]=[i],e}function U(e){V&&(e&&de(e)&&(e=e.split(",")),pa.isArray(e)&&(e=e[0])),!k[n=null==e||""===e?t:e]&&b&&b.length&&(n=b[0].value),L&&(a=k[n]?k[n].group:null)}function q(e){return x[e]||(k[e]?k[e].text:"")}function B(){var e,t="",a=r.getVal(),s=w.formatValue.call(T,r.getArrayVal(),r,!0);if(w.filter&&"inline"==w.display||l.val(s),y.is("select")&&P){if(V)for(e=0;e<a.length;e++)t+='<option value="'+a[e]+'">'+q(a[e])+"</option>";else t='<option value="'+(null===a?"":a)+'">'+s+"</option>";y.html(t)}T!==l[0]&&y.val(a)}function K(){var e={};e[v]=W(),o=!0,r.changeWheel(e)}function G(e,t){return t=t.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&"),e.match(new RegExp(t,"ig"))}function X(e){return w.data.dataField?e[w.data.dataField]:w.data.processResponse?w.data.processResponse(e):e}function Z(e){var t={};R(e),U(n),w.wheels=J(),t[v]=i,r._tempWheelArray[v]=n,L&&(t[d]=s,r._tempWheelArray[d]=a),r.changeWheel(t,0,!0),B()}function Q(e){return r.trigger("onFilter",{filterText:e})}function ee(e){e[d]!=a&&(a=e[d],n=p[a].options[0].value,e[v]=n,O?K():r.setArrayVal(e,!1,!1,!0,g))}return r.setVal=function(e,t,a,s,n){if(N&&(null==e||V||(e=[e]),e&&de(e)&&(e=e.split(",")),r._tempSelected[v]=ae(e),s||(r._selected[v]=ae(e)),e=e?e[0]:null,L)){var i=k[e],o=i&&i.group;r._tempSelected[d]=ae([o]),s||(r._selected[d]=ae([o]))}r._setVal(e,t,a,s,n)},r.getVal=function(e,t){var a;return a=N?(a=te(e?r._tempSelected[v]:r._selected[v]),V?a:a.length?a[0]:null):(a=e?r._tempWheelArray:r._hasValue?r._wheelArray:null)?a[v]:null,V?a:void 0!==a?I&&t?[k[a]?k[a].group:null,a]:a:null},r.refresh=function(e,t,a){a=a||ra,e?(h=e,u||(w.data=e)):pa.isArray(w.data)&&(h=w.data),!e&&u&&void 0===t?zt(w.data.url,function(e){h=X(e),Z(),a()},w.data.dataType):(Z(t),a())},e.invalid||(w.invalid=$),v=L?(d=0,1):(d=-1,0),N&&(V&&y.prop("multiple",!0),z&&de(z)&&(z=z.split(",")),r._selected[v]=ae(z)),r._$input&&r._$input.remove(),y.next().is(".mbsc-select-input")?l=y.next().removeAttr("tabindex"):w.input?l=pa(w.input):(w.filter&&"inline"==w.display?r._$input=pa('<div class="mbsc-sel-input-wrap"><input type="text" id="'+A+'" class="mbsc-select-input mbsc-control '+w.inputClass+'" readonly /></div>'):(l=pa('<input type="text" id="'+A+'" class="mbsc-select-input mbsc-control '+w.inputClass+'" readonly />'),r._$input=l),w.showInput&&(r._$input.insertAfter(y),l=l||r._$input.find("#"+A))),r.attachShow(l.attr("placeholder",w.placeholder||"")),l[0]!==T&&(y.addClass("mbsc-sel-hdn").attr("tabindex",-1),w.showInput||y.attr("data-enhance",!1)),!N||w.rows%2||(w.rows=w.rows-1),w.filter&&(c=w.filter.minLength||0),(u=w.data&&w.data.url)?r.refresh():(P&&(h=w.data),R(),U(y.val())),{layout:S,headerText:!1,anchor:l,compClass:"mbsc-sc mbsc-sel"+(N?" mbsc-sel-multi":""),setOnTap:!L||[!1,!0],formatValue:function(e,t,a){var s,n=[],i=a?t._selected:t._tempSelected;if(N){for(s in i[v])n.push(q(s));return n.join(", ")}return q(e[v])},tapSelect:N,parseValue:function(e){return U(void 0===e?y.val():e),L?[a,n]:[n]},validate:function(e){var t=e.index,a=[];return a[v]=w.invalid,O&&!o&&void 0===t&&K(),o=!1,{disabled:a}},onRead:B,onFill:B,onMarkupReady:function(e,t){if(w.filter){var a,s,n,i=pa(".mbsc-fr-w",e.target),o=pa('<span class="mbsc-sel-filter-clear mbsc-ic mbsc-ic-'+w.filterClearIcon+'"></span>');"inline"==w.display?(a=l).parent().find(".mbsc-sel-filter-clear").remove():(i.find(".mbsc-fr-c").before('<div class="mbsc-input mbsc-sel-filter-cont mbsc-control-w mbsc-'+w.theme+(w.baseTheme?" mbsc-"+w.baseTheme:"")+'"><span class="mbsc-input-wrap"><input tabindex="0" type="text" class="mbsc-sel-filter-input mbsc-control"/></span></div>'),a=i.find(".mbsc-sel-filter-input")),m=null,n=a[0],a.prop("readonly",!1).attr("placeholder",w.filterPlaceholderText).parent().append(o),i.find(".mbsc-fr-c").prepend(M),t._activeElm=n,t.tap(o,function(){m=null,n.value="",t.refresh(),o.removeClass("mbsc-sel-filter-show-clear"),Q("")}),a.on("keydown",function(e){13!=e.keyCode&&27!=e.keyCode||(e.stopPropagation(),n.blur())}).on("input",function(){clearTimeout(s),n.value.length?o.addClass("mbsc-sel-filter-show-clear"):o.removeClass("mbsc-sel-filter-show-clear"),s=setTimeout(function(){m!==n.value&&!1!==Q(n.value)&&((m=n.value).length>=c||!m.length)&&(u&&w.data.remoteFilter?zt(w.data.url+encodeURIComponent(m),function(e){t.refresh(X(e))},w.data.dataType):t.refresh(void 0,m))},500)})}},onBeforeShow:function(){V&&w.counter&&(w.headerText=function(){var e=0;return pa.each(r._tempSelected[v],function(){e++}),(1<e&&w.selectedPluralText||w.selectedText).replace(/{count}/,e)}),U(y.val()),N&&L&&(r._selected[d]=ae([a])),w.filter&&R(void 0),r.settings.wheels=J(),o=!0},onWheelGestureStart:function(e){e.index==d&&(w.readonly=[!1,!0])},onWheelAnimationEnd:function(e){var t=r.getArrayVal(!0);e.index==d?(w.readonly=C,N||ee(t)):e.index==v&&t[v]!=n&&(n=t[v],L&&k[n]&&k[n].group!=a&&(a=k[n].group,t[d]=a,r._tempSelected[d]=ae([a]),r.setArrayVal(t,!1,!1,!0,g)))},onItemTap:function(e){var t;if(e.index==v&&(x[e.value]=k[e.value].text,N&&!V&&e.selected))return!1;if(e.index==d&&N){if(e.selected)return!1;(t=r.getArrayVal(!0))[d]=e.value,ee(t)}},onClose:function(){u&&w.data.remoteFilter&&m&&r.refresh()},onDestroy:function(){r._$input&&r._$input.remove(),y.removeClass("mbsc-sel-hdn").removeAttr("tabindex")}}},f("select",De);var os={autostart:!1,step:1,useShortLabels:!1,labels:["Years","Months","Days","Hours","Minutes","Seconds",""],labelsShort:["Yrs","Mths","Days","Hrs","Mins","Secs",""],startText:"Start",stopText:"Stop",resetText:"Reset",lapText:"Lap",hideText:"Hide",mode:"countdown"};Me.timer=function(a){function c(e){return new Date(e.getUTCFullYear(),e.getUTCMonth(),e.getUTCDate(),e.getUTCHours(),e.getUTCMinutes(),e.getUTCSeconds(),e.getUTCMilliseconds())}function r(e,t,a){return(t||"")+(e<10?"0":"")+e+'<span class="mbsc-timer-lbl">'+a+"</span>"}function s(e){var a,s=[],n=function(a){var s={};if(F&&_[N].index>_.days.index){var e,t,n,i,o=new Date,r=f?o:E,l=f?E:o;for(l=c(l),r=c(r),s.years=r.getFullYear()-l.getFullYear(),s.months=r.getMonth()-l.getMonth(),s.days=r.getDate()-l.getDate(),s.hours=r.getHours()-l.getHours(),s.minutes=r.getMinutes()-l.getMinutes(),s.seconds=r.getSeconds()-l.getSeconds(),s.fract=(r.getMilliseconds()-l.getMilliseconds())/10,e=y.length;0<e;e--)t=y[e-1],n=_[t],i=y[pa.inArray(t,y)-1],_[i]&&s[t]<0&&(s[i]--,s[t]+="months"==i?32-new Date(r.getFullYear(),r.getMonth(),32).getDate():n.until+1);"months"==N&&(s.months+=12*s.years,delete s.years)}else pa(y).each(function(e,t){_[t].index<=_[N].index&&(s[t]=Math.floor(a/_[t].limit),a-=s[t]*_[t].limit)});return s}(e);return pa(y).each(function(e,t){w[t]&&(a=Math.max(Math.round(V/_[t].limit),1),s.push(Math.round(n[t]/a)*a))}),s}function n(e){F?(u=E-new Date,f=u<0&&(u*=-1,!0),D=!(h=0)):(void 0!==E?(D=!1,u=1e3*E,f="countdown"!=x.mode):(u=0,f="countdown"!=x.mode,D=f),e&&(h=0))}function t(){k?(pa(".mbsc-fr-w",p).addClass("mbsc-timer-running mbsc-timer-locked"),pa(".mbsc-timer-btn-toggle-c > div",p).text(x.stopText),a.buttons.start.icon&&pa(".mbsc-timer-btn-toggle-c > div",p).removeClass("mbsc-ic-"+a.buttons.start.icon),a.buttons.stop.icon&&pa(".mbsc-timer-btn-toggle-c > div",p).addClass("mbsc-ic-"+a.buttons.stop.icon),"stopwatch"==x.mode&&(pa(".mbsc-timer-btn-resetlap-c > div",p).text(x.lapText),a.buttons.reset.icon&&pa(".mbsc-timer-btn-resetlap-c > div",p).removeClass("mbsc-ic-"+a.buttons.reset.icon),a.buttons.lap.icon&&pa(".mbsc-timer-btn-resetlap-c > div",p).addClass("mbsc-ic-"+a.buttons.lap.icon))):(pa(".mbsc-fr-w",p).removeClass("mbsc-timer-running"),pa(".mbsc-timer-btn-toggle-c > div",p).text(x.startText),a.buttons.start.icon&&pa(".mbsc-timer-btn-toggle-c > div",p).addClass("mbsc-ic-"+a.buttons.start.icon),a.buttons.stop.icon&&pa(".mbsc-timer-btn-toggle-c > div",p).removeClass("mbsc-ic-"+a.buttons.stop.icon),"stopwatch"==x.mode&&(pa(".mbsc-timer-btn-resetlap-c > div",p).text(x.resetText),a.buttons.reset.icon&&pa(".mbsc-timer-btn-resetlap-c > div",p).addClass("mbsc-ic-"+a.buttons.reset.icon),a.buttons.lap.icon&&pa(".mbsc-timer-btn-resetlap-c > div",p).removeClass("mbsc-ic-"+a.buttons.lap.icon)))}var l,e,m,i,o,d,u,h,f,p,b,v=va({},a.settings),x=va(a.settings,os,v),g=x.useShortLabels?x.labelsShort:x.labels,T=["resetlap","toggle"],y=["years","months","days","hours","minutes","seconds","fract"],_={years:{index:6,until:10,limit:31536e6,label:g[0],wheel:{}},months:{index:5,until:11,limit:2592e6,label:g[1],wheel:{}},days:{index:4,until:31,limit:864e5,label:g[2],wheel:{}},hours:{index:3,until:23,limit:36e5,label:g[3],wheel:{}},minutes:{index:2,until:59,limit:6e4,label:g[4],wheel:{}},seconds:{index:1,until:59,limit:1e3,label:g[5],wheel:{}},fract:{index:0,until:99,limit:10,label:g[6],prefix:".",wheel:{}}},w={},M=[],C=0,k=!1,S=!0,D=!1,V=Math.max(10,1e3*x.step),N=x.maxWheel,A="stopwatch"==x.mode||F,E=x.targetTime,F=E&&void 0!==E.getTime,H=[[]];return a.start=function(){if(S&&a.reset(),!k){if(n(),!D&&u<=h)return;S=!(k=!0),o=new Date,i=h,x.readonly=!0,a.setVal(s(f?h:u-h),!0,!0,!1,100),e=setInterval(function(){h=new Date-o+i,a.setVal(s(f?h:u-h),!0,!0,!1,Math.min(100,m-10)),!D&&u<=h+m&&(clearInterval(e),setTimeout(function(){a.stop(),h=u,a.setVal(s(f?h:0),!0,!0,!1,100),a.trigger("onFinish",{time:u}),S=!0},u-h))},m),t(),a.trigger("onStart")}},a.stop=function(){k&&(k=!1,clearInterval(e),h=new Date-o+i,t(),a.trigger("onStop",{ellapsed:h}))},a.toggle=function(){k?a.stop():a.start()},a.reset=function(){a.stop(),M=[],C=h=0,a.setVal(s(f?0:u),!0,!0,!1,1e3),a.settings.readonly=A,S=!0,A||pa(".mbsc-fr-w",p).removeClass("mbsc-timer-locked"),a.trigger("onReset")},a.lap=function(){k&&(d=new Date-o+i,b=d-C,C=d,M.push(d),a.trigger("onLap",{ellapsed:d,lap:b,laps:M}))},a.resetlap=function(){k&&"stopwatch"==x.mode?a.lap():a.reset()},a.getTime=function(){return u},a.setTime=function(e){E=e/1e3,u=e},a.getEllapsedTime=function(){return S?0:k?new Date-o+i:h},a.setEllapsedTime=function(e,t){S||(i=h=e,o=new Date,a.setVal(s(f?h:u-h),!0,t,!1,1e3))},n(!0),N||u||(N="minutes"),"inline"!==x.display&&T.unshift("hide"),N||pa(y).each(function(e,t){if(!N&&u>=_[t].limit)return N=t,!1}),pa(y).each(function(e,t){!function(e){var t=1,a=_[e],s=a.wheel,n=a.prefix,i=a.until,o=_[y[pa.inArray(e,y)-1]];if(a.index<=_[N].index&&(!o||o.limit>V))if(w[e]||H[0].push(s),w[e]=1,s.data=[],s.label=a.label||"",s.cssClass="mbsc-timer-whl-"+e,V>=a.limit&&(t=Math.max(Math.round(V/a.limit),1),m=t*a.limit),e==N)s.min=0,s.data=function(e){return{value:e,display:r(e,n,a.label)}},s.getIndex=function(e){return e};else for(l=0;l<=i;l+=t)s.data.push({value:l,display:r(l,n,a.label)})}(t)}),m=Math.max(97,m),x.autostart&&setTimeout(function(){a.start()},0),a.handlers.toggle=a.toggle,a.handlers.start=a.start,a.handlers.stop=a.stop,a.handlers.resetlap=a.resetlap,a.handlers.reset=a.reset,a.handlers.lap=a.lap,a.buttons.toggle={parentClass:"mbsc-timer-btn-toggle-c",text:x.startText,icon:x.startIcon,handler:"toggle"},a.buttons.start={text:x.startText,icon:x.startIcon,handler:"start"},a.buttons.stop={text:x.stopText,icon:x.stopIcon,handler:"stop"},a.buttons.reset={text:x.resetText,icon:x.resetIcon,handler:"reset"},a.buttons.lap={text:x.lapText,icon:x.lapIcon,handler:"lap"},a.buttons.resetlap={parentClass:"mbsc-timer-btn-resetlap-c",text:x.resetText,icon:x.resetIcon,handler:"resetlap"},a.buttons.hide={parentClass:"mbsc-timer-btn-hide-c",text:x.hideText,icon:x.closeIcon,handler:"cancel"},{minWidth:100,wheels:H,headerText:!1,readonly:A,buttons:T,compClass:"mbsc-timer mbsc-sc",parseValue:function(){return s(f?0:u)},formatValue:function(a){var s="",n=0;return pa(y).each(function(e,t){"fract"!=t&&w[t]&&(s+=a[n]+("seconds"==t&&w.fract?"."+a[n+1]:"")+" "+g[e]+" ",n++)}),s},validate:function(e){var a=e.values,t=e.index,s=0;S&&void 0!==t&&(E=0,pa(y).each(function(e,t){w[t]&&(E+=_[t].limit*a[s],s++)}),E/=1e3,n(!0))},onBeforeShow:function(){x.showLabel=!0},onMarkupReady:function(e){p=pa(e.target),t(),A&&pa(".mbsc-fr-w",p).addClass("mbsc-timer-locked")},onPosition:function(e){pa(".mbsc-fr-w",e.target).css("min-width",0).css("min-width",pa(".mbsc-fr-btn-cont",e.target)[0].offsetWidth)},onDestroy:function(){clearInterval(e)}}},f("timer",De);var rs={wheelOrder:"hhiiss",useShortLabels:!1,min:0,max:1/0,labels:["Years","Months","Days","Hours","Minutes","Seconds"],labelsShort:["Yrs","Mths","Days","Hrs","Mins","Secs"]};function ls(e){return e<-1e-7?Math.ceil(e-1e-7):Math.floor(e+1e-7)}function cs(e,t,a){e=parseInt(e),t=parseInt(t),a=parseInt(a);var s,n,i,o,r=new Array(0,0,0);return s=1582<e||1582==e&&10<t||1582==e&&10==t&&14<a?ls(1461*(e+4800+ls((t-14)/12))/4)+ls(367*(t-2-12*ls((t-14)/12))/12)-ls(3*ls((e+4900+ls((t-14)/12))/100)/4)+a-32075:367*e-ls(7*(e+5001+ls((t-9)/7))/4)+ls(275*t/9)+a+1729777,o=ls(((n=s-1948440+10632)-1)/10631),i=ls((10985-(n=n-10631*o+354))/5316)*ls(50*n/17719)+ls(n/5670)*ls(43*n/15238),n=n-ls((30-i)/15)*ls(17719*i/50)-ls(i/16)*ls(15238*i/43)+29,t=ls(24*n/709),a=n-ls(709*t/24),e=30*o+i-30,r[2]=a,r[1]=t,r[0]=e,r}Me.timespan=function(d){function u(a){var s={};return pa(b).each(function(e,t){s[t]=g[t]?Math.floor(a/v[t].limit):0,a-=s[t]*v[t].limit}),s}function o(e,t,a){return(e<10&&t?"0":"")+e+'<span class="mbsc-ts-lbl">'+a+"</span>"}function h(a){var s=0;return pa.each(m,function(e,t){isNaN(+a[0])||(s+=v[t.v].limit*a[e])}),s}var r,a,i,f,p,e=va({},d.settings),l=va(d.settings,rs,e),c=l.wheelOrder,t=l.useShortLabels?l.labelsShort:l.labels,b=["years","months","days","hours","minutes","seconds"],v={years:{ord:0,index:6,until:10,limit:31536e6,label:t[0],re:"y",wheel:{}},months:{ord:1,index:5,until:11,limit:2592e6,label:t[1],re:"m",wheel:{}},days:{ord:2,index:4,until:31,limit:864e5,label:t[2],re:"d",wheel:{}},hours:{ord:3,index:3,until:23,limit:36e5,label:t[3],re:"h",wheel:{}},minutes:{ord:4,index:2,until:59,limit:6e4,label:t[4],re:"i",wheel:{}},seconds:{ord:5,index:1,until:59,limit:1e3,label:t[5],re:"s",wheel:{}}},m=[],x=l.steps||[],g={},T="seconds",y=l.defaultValue||Math.max(l.min,Math.min(0,l.max)),s=[[]];return pa(b).each(function(e,t){-1<(a=c.search(new RegExp(v[t].re,"i")))&&(m.push({o:a,v:t}),v[t].index>v[T].index&&(T=t))}),m.sort(function(e,t){return e.o>t.o?1:-1}),pa.each(m,function(e,t){g[t.v]=e+1,s[0].push(v[t.v].wheel)}),f=u(l.min),p=u(l.max),pa.each(m,function(e,t){!function(e){var t=!1,a=x[g[e]-1]||1,s=v[e],n=s.label,i=s.wheel;if(i.data=[],i.label=s.label,c.match(new RegExp(s.re+s.re,"i"))&&(t=!0),e==T)i.min=f[e],i.max=p[e],i.data=function(e){return{value:e*a,display:o(e*a,t,n)}},i.getIndex=function(e){return Math.round(e/a)};else for(r=0;r<=s.until;r+=a)i.data.push({value:r,display:o(r,t,n)})}(t.v)}),d.getVal=function(e,t){return t?d._getVal(e):d._hasValue||e?h(d.getArrayVal(e)):null},{minWidth:100,showLabel:!0,wheels:s,compClass:"mbsc-ts mbsc-sc",parseValue:function(a){var s,n=[];return la(a)||!a?(i=u(a||y),pa.each(m,function(e,t){n.push(i[t.v])})):pa.each(m,function(e,t){s=new RegExp("(\\d+)\\s?("+l.labels[v[t.v].ord]+"|"+l.labelsShort[v[t.v].ord]+")","gi").exec(a),n.push(s?s[1]:0)}),pa(n).each(function(e,t){n[e]=function(e,t){return Math.floor(e/t)*t}(t,x[e]||1)}),n},formatValue:function(a){var s="";return pa.each(m,function(e,t){s+=+a[e]?a[e]+" "+v[t.v].label+" ":""}),s?s.replace(/\s+$/g,""):0},validate:function(e){var a,s,n,i,o=e.values,r=e.direction,l=[],c=!0,m=!0;return pa(b).each(function(e,t){if(void 0!==g[t]){if(n=g[t]-1,l[n]=[],i={},t!=T){if(c)for(s=p[t]+1;s<=v[t].until;s++)i[s]=!0;if(m)for(s=0;s<f[t];s++)i[s]=!0}o[n]=d.getValidValue(n,o[n],r,i),a=u(h(o)),c=c&&a[t]==p[t],m=m&&a[t]==f[t],pa.each(i,function(e){l[n].push(e)})}}),{disabled:l}}}},f("timespan",De),Me.treelist=Ca,f("treelist",De),f("popup",Lt,!1),f("widget",Ot,!1),ie.hijri={getYear:function(e){return cs(e.getFullYear(),e.getMonth()+1,e.getDate())[0]},getMonth:function(e){return--cs(e.getFullYear(),e.getMonth()+1,e.getDate())[1]},getDay:function(e){return cs(e.getFullYear(),e.getMonth()+1,e.getDate())[2]},getDate:function(e,t,a,s,n,i,o){t<0&&(e+=Math.floor(t/12),t=12+t%12),11<t&&(e+=Math.floor(t/12),t%=12);var r=function(e,t,a){e=parseInt(e),t=parseInt(t),a=parseInt(a);var s,n,i,o,r,l,c=new Array(3);return e=2299160<(s=ls((11*e+3)/30)+354*e+30*t-ls((t-1)/2)+a+1948440-385)?(o=ls(4*(n=68569+s)/146097),n-=ls((146097*o+3)/4),r=ls(4e3*(n+1)/1461001),n=n-ls(1461*r/4)+31,i=ls(80*n/2447),a=n-ls(2447*i/80),t=i+2-12*(n=ls(i/11)),100*(o-49)+r+n):(l=ls(((i=1402+s)-1)/1461),o=ls(((n=i-1461*l)-1)/365)-ls(n/1461),i=ls(80*(r=n-365*o+30)/2447),a=r-ls(2447*i/80),t=i+2-12*(r=ls(i/11)),4*l+o+r-4716),c[2]=a,c[1]=t,c[0]=e,c}(e,+t+1,a);return new Date(r[0],r[1]-1,r[2],s||0,n||0,i||0,o||0)},getMaxDayOfMonth:function(e,t){return[30,29,30,29,30,29,30,29,30,29,30,29][t]+(11===t&&(11*e+14)%30<11?1:0)}},Z.i18n.ar={rtl:!0,setText:"تعيين",cancelText:"إلغاء",clearText:"مسح",selectedText:"{count} المحدد",dateFormat:"dd/mm/yy",dayNames:["الأحد","الاثنين","الثلاثاء","الأربعاء","الخميس","الجمعة","السبت"],dayNamesShort:["أحد","اثنين","ثلاثاء","أربعاء","خميس","جمعة","سبت"],dayNamesMin:["ح","ن","ث","ر","خ","ج","س"],dayText:"يوم",hourText:"ساعات",minuteText:"الدقائق",monthNames:["كانون الثاني","شهر فبراير","مارس","أبريل","قد","يونيو","يوليو","أغسطس","سبتمبر","شهر اكتوبر","شهر نوفمبر","ديسمبر"],monthNamesShort:["كانون الثاني","شهر فبراير","مارس","أبريل","قد","يونيو","يوليو","أغسطس","سبتمبر","شهر اكتوبر","شهر نوفمبر","ديسمبر"],monthText:"شهر",secText:"ثواني",amText:"ص",pmText:"م",timeFormat:"hh:ii A",yearText:"عام",nowText:"الآن",firstDay:0,dateText:"تاريخ",timeText:"وقت",closeText:"إغلاق",todayText:"اليوم",prevMonthText:"الشهر السابق",nextMonthText:"الشهر القادم",prevYearText:"السنه السابقة",nextYearText:"العام القادم",allDayText:"اليوم كله",noEventsText:"لا توجد احداث",eventText:"الحدث",eventsText:"أحداث",moreEventsText:"واحد آخر",moreEventsPluralText:"اثنان آخران {count}",fromText:"يبدا",toText:"ينتهي",wholeText:"كامل",fractionText:"جزء",unitText:"وحدة",delimiter:"/",decimalSeparator:".",thousandsSeparator:",",labels:["سنوات","أشهر","أيام","ساعة","دقائق","ثواني",""],labelsShort:["سنوات","أشهر","أيام","ساعة","دقائق","ثواني",""],startText:"بدء",stopText:"إيقاف",resetText:"إعادة ضبط",lapText:"الدورة",hideText:"إخفاء",offText:"إيقاف",onText:"تشغيل",backText:"رجوع",undoText:"تراجع"},Z.i18n.bg={setText:"Задаване",cancelText:"Отмяна",clearText:"Изчистване",selectedText:"{count} подбран",dateFormat:"dd.mm.yy",dayNames:["Неделя","Понеделник","Вторник","Сряда","Четвъртък","Петък","Събота"],dayNamesShort:["Нед","Пон","Вто","Сря","Чет","Пет","Съб"],dayNamesMin:["Не","По","Вт","Ср","Че","Пе","Съ"],dayText:"ден",delimiter:".",hourText:"час",minuteText:"минута",monthNames:["Януари","Февруари","Март","Април","Май","Юни","Юли","Август","Септември","Октомври","Ноември","Декември"],monthNamesShort:["Яну","Фев","Мар","Апр","Май","Юни","Юли","Авг","Сеп","Окт","Нов","Дек"],monthText:"месец",secText:"секунди",timeFormat:"H:ii",yearText:"година",nowText:"Сега",pmText:"pm",amText:"am",firstDay:1,dateText:"Дата",timeText:"път",todayText:"днес",prevMonthText:"Предишния месец",nextMonthText:"Следващият месец",prevYearText:"Предходната година",nextYearText:"Следващата година",closeText:"затвори",eventText:"Събитие",eventsText:"Събития",allDayText:"Цял ден",noEventsText:"Няма събития",moreEventsText:"Още {count}",fromText:"ОТ",toText:"ДО",wholeText:"цяло",fractionText:"фракция",unitText:"единица",labels:["Години","месеца","дни","часа","минути","секунди",""],labelsShort:["Години","месеца","дни","часа","минути","секунди",""],startText:"Старт",stopText:"Стоп",resetText:"Нулиране",lapText:"Обиколка",hideText:"крия",backText:"връщане",undoText:"ОТМЯНА",offText:"ИЗКЛ",onText:"ВКЛ",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.ca={setText:"Acceptar",cancelText:"Cancel·lar",clearText:"Esborrar",selectedText:"{count} seleccionat",selectedPluralText:"{count} seleccionats",dateFormat:"dd/mm/yy",dayNames:["Diumenge","Dilluns","Dimarts","Dimecres","Dijous","Divendres","Dissabte"],dayNamesShort:["Dg","Dl","Dt","Dc","Dj","Dv","Ds"],dayNamesMin:["Dg","Dl","Dt","Dc","Dj","Dv","Ds"],dayText:"Dia",hourText:"Hores",minuteText:"Minuts",monthNames:["Gener","Febrer","Març","Abril","Maig","Juny","Juliol","Agost","Setembre","Octubre","Novembre","Desembre"],monthNamesShort:["Gen","Feb","Mar","Abr","Mai","Jun","Jul","Ago","Set","Oct","Nov","Des"],monthText:"Mes",secText:"Segons",timeFormat:"HH:ii",yearText:"Any",nowText:"Ara",pmText:"pm",amText:"am",todayText:"Avui",firstDay:1,dateText:"Data",timeText:"Temps",closeText:"Tancar",allDayText:"Tot el dia",noEventsText:"Cap esdeveniment",eventText:"Esdeveniments",eventsText:"Esdeveniments",moreEventsText:"{count} més",fromText:"Iniciar",toText:"Final",wholeText:"Sencer",fractionText:"Fracció",unitText:"Unitat",labels:["Anys","Mesos","Dies","Hores","Minuts","Segons",""],labelsShort:["Anys","Mesos","Dies","Hrs","Mins","Secs",""],startText:"Iniciar",stopText:"Aturar",resetText:"Reiniciar",lapText:"Volta",hideText:"Amagar",backText:"Enrere",undoText:"Desfés",offText:"No",onText:"Si"},Z.i18n.cs={setText:"Zadej",cancelText:"Storno",clearText:"Vymazat",selectedText:"Označený: {count}",dateFormat:"dd.mm.yy",dayNames:["Neděle","Pondělí","Úterý","Středa","Čtvrtek","Pátek","Sobota"],dayNamesShort:["Ne","Po","Út","St","Čt","Pá","So"],dayNamesMin:["N","P","Ú","S","Č","P","S"],dayText:"Den",hourText:"Hodiny",minuteText:"Minuty",monthNames:["Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec"],monthNamesShort:["Led","Úno","Bře","Dub","Kvě","Čer","Čvc","Spr","Zář","Říj","Lis","Pro"],monthText:"Měsíc",secText:"Sekundy",timeFormat:"HH:ii",yearText:"Rok",nowText:"Teď",amText:"am",pmText:"pm",todayText:"Dnes",firstDay:1,dateText:"Datum",timeText:"Čas",closeText:"Zavřít",allDayText:"Celý den",noEventsText:"Žádné události",eventText:"Událostí",eventsText:"Události",moreEventsText:"{count} další",fromText:"Začátek",toText:"Konec",wholeText:"Celý",fractionText:"Část",unitText:"Jednotka",labels:["Roky","Měsíce","Dny","Hodiny","Minuty","Sekundy",""],labelsShort:["Rok","Měs","Dny","Hod","Min","Sec",""],startText:"Start",stopText:"Stop",resetText:"Resetovat",lapText:"Etapa",hideText:"Schovat",backText:"Zpět",undoText:"Zpět",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.da={setText:"Sæt",cancelText:"Annuller",clearText:"Ryd",selectedText:"{count} valgt",selectedPluralText:"{count} valgt",dateFormat:"dd/mm/yy",dayNames:["Søndag","Mandag","Tirsdag","Onsdag","Torsdag","Fredag","Lørdag"],dayNamesShort:["Søn","Man","Tir","Ons","Tor","Fre","Lør"],dayNamesMin:["S","M","T","O","T","F","L"],dayText:"Dag",hourText:"Timer",minuteText:"Minutter",monthNames:["Januar","Februar","Marts","April","Maj","Juni","Juli","August","September","Oktober","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","Maj","Jun","Jul","Aug","Sep","Okt","Nov","Dec"],monthText:"Måned",secText:"Sekunder",amText:"am",pmText:"pm",timeFormat:"HH.ii",yearText:"År",nowText:"Nu",todayText:"I dag",firstDay:1,dateText:"Dato",timeText:"Tid",closeText:"Luk",allDayText:"Hele dagen",noEventsText:"Ingen begivenheder",eventText:"Begivenheder",eventsText:"Begivenheder",moreEventsText:"{count} mere",fromText:"Start",toText:"Slut",wholeText:"Hele",fractionText:"Dele",unitText:"Enhed",labels:["År","Måneder","Dage","Timer","Minutter","Sekunder",""],labelsShort:["År","Mdr","Dg","Timer","Min","Sek",""],startText:"Start",stopText:"Stop",resetText:"Nulstil",lapText:"Omgang",hideText:"Skjul",offText:"Fra",onText:"Til",backText:"Tilbage",undoText:"Fortryd"},Z.i18n.de={setText:"OK",cancelText:"Abbrechen",clearText:"Löschen",selectedText:"{count} ausgewählt",dateFormat:"dd.mm.yy",dayNames:["Sonntag","Montag","Dienstag","Mittwoch","Donnerstag","Freitag","Samstag"],dayNamesShort:["So","Mo","Di","Mi","Do","Fr","Sa"],dayNamesMin:["S","M","D","M","D","F","S"],dayText:"Tag",delimiter:".",hourText:"Stunde",minuteText:"Minuten",monthNames:["Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"],monthNamesShort:["Jan","Feb","Mär","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"],monthText:"Monat",secText:"Sekunden",timeFormat:"HH:ii",yearText:"Jahr",nowText:"Jetzt",pmText:"pm",amText:"am",todayText:"Heute",firstDay:1,dateText:"Datum",timeText:"Zeit",closeText:"Schließen",allDayText:"Ganztägig",noEventsText:"Keine Ereignisse",eventText:"Ereignis",eventsText:"Ereignisse",moreEventsText:"{count} weiteres Element",moreEventsPluralText:"{count} weitere Elemente",fromText:"Von",toText:"Bis",wholeText:"Ganze Zahl",fractionText:"Bruchzahl",unitText:"Maßeinheit",labels:["Jahre","Monate","Tage","Stunden","Minuten","Sekunden",""],labelsShort:["Jahr.","Mon.","Tag.","Std.","Min.","Sek.",""],startText:"Starten",stopText:"Stoppen",resetText:"Zurücksetzen",lapText:"Lap",hideText:"Ausblenden",backText:"Zurück",undoText:"Rückgängig machen",offText:"Aus",onText:"Ein",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.el={setText:"Ορισμος",cancelText:"Ακυρωση",clearText:"Διαγραφη",selectedText:"{count} επιλεγμένα",dateFormat:"dd/mm/yy",dayNames:["Κυριακή","Δευτέρα","Τρίτη","Τετάρτη","Πέμπτη","Παρασκευή","Σάββατο"],dayNamesShort:["Κυρ","Δευ","Τρι","Τετ","Πεμ","Παρ","Σαβ"],dayNamesMin:["Κυ","Δε","Τρ","Τε","Πε","Πα","Σα"],dayText:"ημέρα",delimiter:"/",hourText:"ώρα",minuteText:"λεπτό",monthNames:["Ιανουάριος","Φεβρουάριος","Μάρτιος","Απρίλιος","Μάιος","Ιούνιος","Ιούλιος","Αύγουστος","Σεπτέμβριος","Οκτώβριος","Νοέμβριος","Δεκέμβριος"],monthNamesShort:["Ιαν","Φεβ","Μαρ","Απρ","Μαι","Ιουν","Ιουλ","Αυγ","Σεπ","Οκτ","Νοε","Δεκ"],monthText:"Μήνας",secText:"δευτερόλεπτα",timeFormat:"H:ii",yearText:"έτος",nowText:"τώρα",pmText:"μμ",amText:"πμ",firstDay:1,dateText:"Ημερομηνία",timeText:"φορά",todayText:"Σήμερα",prevMonthText:"Προηγούμενο μήνα",nextMonthText:"Επόμενο μήνα",prevYearText:"Προηγούμενο έτος",nextYearText:"Επόμενο έτος",closeText:"Κλείσιμο",eventText:"Γεγονότα",eventsText:"Γεγονότα",allDayText:"Ολοήμερο",noEventsText:"Δεν υπάρχουν γεγονότα",moreEventsText:"{count} ακόμη",fromText:"Αρχή",toText:"Τέλος",wholeText:"Ολόκληρος",fractionText:"κλάσμα",unitText:"Μονάδα",labels:["Χρόνια","Μήνες","Ημέρες","Ωρες","Λεπτά","δευτερόλεπτα",""],labelsShort:["Χρόνια","Μήνες","Ημέρες","Ωρες","Λεπτά","δευτ",""],startText:"΄Εναρξη",stopText:"Διακοπή",resetText:"Επαναφορά",lapText:"Γύρος",hideText:"κρύβω",backText:"Πίσω",undoText:"Αναιρεση",offText:"Ανενεργό",onText:"Ενεργό",decimalSeparator:",",thousandsSeparator:" "},Z.i18n["en-GB"]=Z.i18n["en-UK"]={dateFormat:"dd/mm/yy",timeFormat:"HH:ii"},Z.i18n.es={setText:"Aceptar",cancelText:"Cancelar",clearText:"Borrar",selectedText:"{count} seleccionado",selectedPluralText:"{count} seleccionados",dateFormat:"dd/mm/yy",dayNames:["Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado"],dayNamesShort:["Do","Lu","Ma","Mi","Ju","Vi","Sá"],dayNamesMin:["D","L","M","M","J","V","S"],dayText:"Día",hourText:"Horas",minuteText:"Minutos",monthNames:["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"],monthNamesShort:["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"],monthText:"Mes",secText:"Segundos",timeFormat:"HH:ii",yearText:"A&ntilde;o",nowText:"Ahora",pmText:"pm",amText:"am",todayText:"Hoy",firstDay:1,dateText:"Fecha",timeText:"Tiempo",closeText:"Cerrar",allDayText:"Todo el día",noEventsText:"No hay eventos",eventText:"Evento",eventsText:"Eventos",moreEventsText:"{count} más",fromText:"Iniciar",toText:"Final",wholeText:"Entero",fractionText:"Fracción",unitText:"Unidad",labels:["Años","Meses","Días","Horas","Minutos","Segundos",""],labelsShort:["Año","Mes","Día","Hora","Min","Seg",""],startText:"Iniciar",stopText:"Deténgase",resetText:"Reinicializar",lapText:"Lap",hideText:"Esconder",backText:"Atrás",undoText:"Deshacer",offText:"No",onText:"Sí",decimalSeparator:",",thousandsSeparator:" "};var ms=[31,28,31,30,31,30,31,31,30,31,30,31],ds=[31,31,31,31,31,31,30,30,30,30,30,29];function us(e,t,a){var s,n=(e=parseInt(e))-1600,i=(t=parseInt(t))-1,o=(a=parseInt(a))-1,r=365*n+parseInt((3+n)/4)-parseInt((99+n)/100)+parseInt((399+n)/400);for(s=0;s<i;++s)r+=ms[s];1<i&&(n%4==0&&n%100!=0||n%400==0)&&++r;var l=(r+=o)-79,c=parseInt(l/12053);l%=12053;var m=979+33*c+4*parseInt(l/1461);for(366<=(l%=1461)&&(m+=parseInt((l-1)/365),l=(l-1)%365),s=0;s<11&&l>=ds[s];++s)l-=ds[s];return[m,s+1,l+1]}ie.jalali={getYear:function(e){return us(e.getFullYear(),e.getMonth()+1,e.getDate())[0]},getMonth:function(e){return--us(e.getFullYear(),e.getMonth()+1,e.getDate())[1]},getDay:function(e){return us(e.getFullYear(),e.getMonth()+1,e.getDate())[2]},getDate:function(e,t,a,s,n,i,o){t<0&&(e+=Math.floor(t/12),t=12+t%12),11<t&&(e+=Math.floor(t/12),t%=12);var r=function(e,t,a){var s,n=(e=parseInt(e))-979,i=(t=parseInt(t))-1,o=(a=parseInt(a))-1,r=365*n+8*parseInt(n/33)+parseInt((n%33+3)/4);for(s=0;s<i;++s)r+=ds[s];var l=(r+=o)+79,c=1600+400*parseInt(l/146097),m=!0;for(36525<=(l%=146097)&&(l--,c+=100*parseInt(l/36524),365<=(l%=36524)?l++:m=!1),c+=4*parseInt(l/1461),366<=(l%=1461)&&(m=!1,l--,c+=parseInt(l/365),l%=365),s=0;ms[s]+(1==s&&m)<=l;s++)l-=ms[s]+(1==s&&m);return[c,s+1,l+1]}(e,+t+1,a);return new Date(r[0],r[1]-1,r[2],s||0,n||0,i||0,o||0)},getMaxDayOfMonth:function(e,t){for(var a,s,n,i=31;!1==(s=t+1,n=i,!((a=e)<0||32767<a||s<1||12<s||n<1||n>ds[s-1]+(12==s&&(a-979)%33%4==0)));)i--;return i}},Z.i18n.fa={setText:"تاييد",cancelText:"انصراف",clearText:"واضح ",selectedText:"{count} منتخب",calendarSystem:"jalali",dateFormat:"yy/mm/dd",dayNames:["يکشنبه","دوشنبه","سه‌شنبه","چهارشنبه","پنج‌شنبه","جمعه","شنبه"],dayNamesShort:["ی","د","س","چ","پ","ج","ش"],dayNamesMin:["ی","د","س","چ","پ","ج","ش"],dayText:"روز",hourText:"ساعت",minuteText:"دقيقه",monthNames:["فروردين","ارديبهشت","خرداد","تير","مرداد","شهريور","مهر","آبان","آذر","دی","بهمن","اسفند"],monthNamesShort:["فروردين","ارديبهشت","خرداد","تير","مرداد","شهريور","مهر","آبان","آذر","دی","بهمن","اسفند"],monthText:"ماه",secText:"ثانيه",timeFormat:"HH:ii",timeWheels:"iiHH",yearText:"سال",nowText:"اکنون",amText:"ب",pmText:"ص",todayText:"امروز",firstDay:6,rtl:!0,dateText:"تاریخ ",timeText:"زمان ",closeText:"نزدیک",allDayText:"تمام روز",noEventsText:"هیچ رویداد",eventText:"رویداد",eventsText:"رویدادها",moreEventsText:"{count} مورد دیگر",fromText:"شروع ",toText:"پایان",wholeText:"تمام",fractionText:"کسر",unitText:"واحد",labels:["سال","ماه","روز","ساعت","دقیقه","ثانیه",""],labelsShort:["سال","ماه","روز","ساعت","دقیقه","ثانیه",""],startText:"شروع",stopText:"پايان",resetText:"تنظیم مجدد",lapText:"Lap",hideText:"پنهان کردن",backText:"پشت",undoText:"واچیدن"},Z.i18n.fi={setText:"Aseta",cancelText:"Peruuta",clearText:"Tyhjennä",selectedText:"{count} valita",dateFormat:"d. MM yy",dayNames:["Sunnuntai","Maanantai","Tiistai","Keskiviiko","Torstai","Perjantai","Lauantai"],dayNamesShort:["Su","Ma","Ti","Ke","To","Pe","La"],dayNamesMin:["S","M","T","K","T","P","L"],dayText:"Päivä",delimiter:".",hourText:"Tuntia",minuteText:"Minuutti",monthNames:["Tammikuu","Helmikuu","Maaliskuu","Huhtikuu","Toukokuu","Kesäkuu","Heinäkuu","Elokuu","Syyskuu","Lokakuu","Marraskuu","Joulukuu"],monthNamesShort:["Tam","Hel","Maa","Huh","Tou","Kes","Hei","Elo","Syy","Lok","Mar","Jou"],monthText:"Kuukausi",secText:"Sekunda",timeFormat:"H:ii",yearText:"Vuosi",nowText:"Nyt",pmText:"pm",amText:"am",firstDay:1,dateText:"Päiväys",timeText:"Aika",todayText:"Tänään",prevMonthText:"Edellinen kuukausi",nextMonthText:"Ensi kuussa",prevYearText:"Edellinen vuosi",nextYearText:"Ensi vuosi",closeText:"Sulje",eventText:"Tapahtumia",eventsText:"Tapahtumia",allDayText:"Koko päivä",noEventsText:"Ei tapahtumia",moreEventsText:"{count} muu",moreEventsPluralText:"{count} muuta",fromText:"Alkaa",toText:"Päättyy",wholeText:"Kokonainen",fractionText:"Murtoluku",unitText:"Yksikkö",labels:["Vuosi","Kuukausi","Päivä","Tunnin","Minuutti","sekuntia",""],labelsShort:["Vuo","Kuu","Päi","Tun","Min","Sek",""],startText:"Käynnistys",stopText:"Seis",resetText:"Aseta uudelleen",lapText:"Kierros",hideText:"Vuota",backText:"Edellinen",undoText:"Kumoa",offText:"Pois",onText:"Päällä",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.fr={setText:"Terminer",cancelText:"Annuler",clearText:"Effacer",selectedText:"{count} sélectionné",selectedPluralText:"{count} sélectionnés",dateFormat:"dd/mm/yy",dayNames:["Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"],dayNamesShort:["Dim.","Lun.","Mar.","Mer.","Jeu.","Ven.","Sam."],dayNamesMin:["D","L","M","M","J","V","S"],dayText:"Jour",monthText:"Mois",monthNames:["Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"],monthNamesShort:["Janv.","Févr.","Mars","Avril","Mai","Juin","Juil.","Août","Sept.","Oct.","Nov.","Déc."],hourText:"Heures",minuteText:"Minutes",secText:"Secondes",timeFormat:"HH:ii",yearText:"Année",nowText:"Maintenant",pmText:"pm",amText:"am",todayText:"Aujourd'hui",firstDay:1,dateText:"Date",timeText:"Heure",closeText:"Fermer",allDayText:"Toute la journée",noEventsText:"Aucun événement",eventText:"Événement",eventsText:"Événements",moreEventsText:"{count} autre",moreEventsPluralText:"{count} autres",fromText:"Démarrer",toText:"Fin",wholeText:"Entier",fractionText:"Fraction",unitText:"Unité",labels:["Ans","Mois","Jours","Heures","Minutes","Secondes",""],labelsShort:["Ans","Mois","Jours","Hrs","Min","Sec",""],startText:"Démarrer",stopText:"Arrêter",resetText:"Réinitialiser",lapText:"Lap",hideText:"Cachez",backText:"Retour",undoText:"Annuler",offText:"Non",onText:"Oui",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.he={rtl:!0,setText:"שמירה",cancelText:"ביטול",clearText:"נקה",selectedText:"{count} נבחר",selectedPluralText:"{count} נבחרו",dateFormat:"dd/mm/yy",dayNames:["ראשון","שני","שלישי","רביעי","חמישי","שישי","שבת"],dayNamesShort:["א'","ב'","ג'","ד'","ה'","ו'","ש'"],dayNamesMin:["א","ב","ג","ד","ה","ו","ש"],dayText:"יום",hourText:"שעות",minuteText:"דקות",monthNames:["ינואר","פברואר","מרץ","אפריל","מאי","יוני","יולי","אוגוסט","ספטמבר","אוקטובר","נובמבר","דצמבר"],monthNamesShort:["ינו","פבר","מרץ","אפר","מאי","יונ","יול","אוג","ספט","אוק","נוב","דצמ"],monthText:"חודש",secText:"שניות",amText:"am",pmText:"pm",timeFormat:"HH:ii",timeWheels:"iiHH",yearText:"שנה",nowText:"עכשיו",firstDay:0,dateText:"תאריך",timeText:"זמן",closeText:"סגירה",todayText:"היום",allDayText:"כל היום",noEventsText:"אין אירועים",eventText:"מִקרֶה",eventsText:"מִקרֶה",moreEventsText:"אירוע אחד נוסף",moreEventsPluralText:"{count} אירועים נוספים",fromText:"התחלה",toText:"סיום",wholeText:"כֹּל",fractionText:"שבריר",unitText:"יחידה",labels:["שנים","חודשים","ימים","שעות","דקות","שניים",""],labelsShort:["שנים","חודשים","ימים","שעות","דקות","שניים",""],startText:"התחל",stopText:"עצור",resetText:"אתחול",lapText:"הקפה",hideText:"הסתר",offText:"כיבוי",onText:"הפעלה",backText:"חזור",undoText:"ביטול פעולה"},Z.i18n.hi={setText:"सैट करें",cancelText:"रद्द करें",clearText:"साफ़ को",selectedText:"{count} चयनित",dateFormat:"dd/mm/yy",dayNames:["रविवार","सोमवार","मंगलवार","बुधवार","गुरुवार","शुक्रवार","शनिवार"],dayNamesShort:["रवि","सोम","मंगल","बुध","गुरु","शुक्र","शनि"],dayNamesMin:["रवि","सोम","मंगल","बुध","गुरु","शुक्र","शनि"],dayText:"दिन",delimiter:".",hourText:"घंटा",minuteText:"मिनट",monthNames:["जनवरी ","फरवरी","मार्च","अप्रेल","मई","जून","जूलाई","अगस्त ","सितम्बर","अक्टूबर","नवम्बर","दिसम्बर"],monthNamesShort:["जन","फर","मार्च","अप्रेल","मई","जून","जूलाई","अग","सित","अक्ट","नव","दि"],monthText:"महीना",secText:"सेकंड",timeFormat:"H:ii",yearText:"साल",nowText:"अब",pmText:"अपराह्न",amText:"पूर्वाह्न",firstDay:1,dateText:"तिथि",timeText:"समय",todayText:"आज",prevMonthText:"पिछ्ला महिना",nextMonthText:"अगले महीने",prevYearText:"पिछला साल",nextYearText:"अगले वर्ष",closeText:"बंद",eventText:"इवेट३",eventsText:"इवेट३",allDayText:"पूरे दिन",noEventsText:"Ei tapahtumia",moreEventsText:"{count} और",fromText:"से",toText:"तक",wholeText:"समूचा",fractionText:"अंश",unitText:"इकाई",labels:["साल","महीने","दिन","घंटे","मिनट","सेकंड",""],labelsShort:["साल","महीने","दिन","घंटे","मिनट","सेकंड",""],startText:"प्रारंभ",stopText:"रोकें",resetText:"रीसेट करें",lapText:"लैप",hideText:"छिपाना",backText:"वापस",undoText:"वापस लाएं",offText:"बंद",onText:"चालू",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.hr={setText:"Postavi",cancelText:"Izlaz",clearText:"Izbriši",selectedText:"{count} odabran",dateFormat:"dd.mm.yy",dayNames:["Nedjelja","Ponedjeljak","Utorak","Srijeda","Četvrtak","Petak","Subota"],dayNamesShort:["Ned","Pon","Uto","Sri","Čet","Pet","Sub"],dayNamesMin:["Ne","Po","Ut","Sr","Če","Pe","Su"],dayText:"Dan",delimiter:".",hourText:"Sat",minuteText:"Minuta",monthNames:["Siječanj","Veljača","Ožujak","Travanj","Svibanj","Lipanj","Srpanj","Kolovoz","Rujan","Listopad","Studeni","Prosinac"],monthNamesShort:["Sij","Velj","Ožu","Tra","Svi","Lip","Srp","Kol","Ruj","Lis","Stu","Pro"],monthText:"Mjesec",secText:"Sekunda",timeFormat:"H:ii",yearText:"Godina",nowText:"Sada",pmText:"pm",amText:"am",firstDay:1,dateText:"Datum",timeText:"Vrijeme",todayText:"Danas",prevMonthText:"Prethodni mjesec",nextMonthText:"Sljedeći mjesec",prevYearText:"Prethodni godina",nextYearText:"Slijedeće godine",closeText:"Zatvori",eventText:"Događaj",eventsText:"događaja",allDayText:"Cijeli dan",noEventsText:"Bez događaja",moreEventsText:"Još {count}",fromText:"Počinje",toText:"Završava",wholeText:"Cjelina",fractionText:"Frakcija",unitText:"Jedinica",labels:["godina","mjesec","dan","sat","minuta","sekunda",""],labelsShort:["god","mje","dan","sat","min","sec",""],startText:"Početak",stopText:"Prekid",resetText:"Resetiraj",lapText:"Ciklus",hideText:"Sakriti",backText:"Natrag",undoText:"Poništavanje",offText:"Uklj.",onText:"Isklj.",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.hu={setText:"OK",cancelText:"Mégse",clearText:"Törlés",selectedText:"{count} kiválasztva",dateFormat:"yy.mm.dd.",dayNames:["Vasárnap","Hétfő","Kedd","Szerda","Csütörtök","Péntek","Szombat"],dayNamesShort:["Va","Hé","Ke","Sze","Csü","Pé","Szo"],dayNamesMin:["V","H","K","Sz","Cs","P","Sz"],dayText:"Nap",delimiter:".",hourText:"Óra",minuteText:"Perc",monthNames:["Január","Február","Március","Április","Május","Június","Július","Augusztus","Szeptember","Október","November","December"],monthNamesShort:["Jan","Feb","Már","Ápr","Máj","Jún","Júl","Aug","Szep","Okt","Nov","Dec"],monthText:"Hónap",secText:"Másodperc",timeFormat:"H:ii",yearText:"Év",nowText:"Most",pmText:"pm",amText:"am",firstDay:1,dateText:"Dátum",timeText:"Idő",todayText:"Ma",prevMonthText:"Előző hónap",nextMonthText:"Következő hónap",prevYearText:"Előző év",nextYearText:"Következő év",closeText:"Bezár",eventText:"esemény",eventsText:"esemény",allDayText:"Egész nap",noEventsText:"Nincs esemény",moreEventsText:"{count} további",fromText:"Eleje",toText:"Vége",wholeText:"Egész",fractionText:"Tört",unitText:"Egység",labels:["Év","Hónap","Nap","Óra","Perc","Másodperc",""],labelsShort:["Év","Hó.","Nap","Óra","Perc","Mp.",""],startText:"Indít",stopText:"Megállít",resetText:"Visszaállít",lapText:"Lap",hideText:"Elrejt",backText:"Vissza",undoText:"Visszavon",offText:"Ki",onText:"Be",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.it={setText:"OK",cancelText:"Annulla",clearText:"Chiarire",selectedText:"{count} selezionato",selectedPluralText:"{count} selezionati",dateFormat:"dd/mm/yy",dayNames:["Domenica","Lunedì","Mertedì","Mercoledì","Giovedì","Venerdì","Sabato"],dayNamesShort:["Do","Lu","Ma","Me","Gi","Ve","Sa"],dayNamesMin:["D","L","M","M","G","V","S"],dayText:"Giorno",hourText:"Ore",minuteText:"Minuti",monthNames:["Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre"],monthNamesShort:["Gen","Feb","Mar","Apr","Mag","Giu","Lug","Ago","Set","Ott","Nov","Dic"],monthText:"Mese",secText:"Secondi",timeFormat:"HH:ii",yearText:"Anno",nowText:"Ora",pmText:"pm",amText:"am",todayText:"Oggi",firstDay:1,dateText:"Data",timeText:"Volta",closeText:"Chiudere",allDayText:"Tutto il giorno",noEventsText:"Nessun evento",eventText:"Evento",eventsText:"Eventi",moreEventsText:"{count} altro",moreEventsPluralText:"altri {count}",fromText:"Inizio",toText:"Fine",wholeText:"Intero",fractionText:"Frazione",unitText:"Unità",labels:["Anni","Mesi","Giorni","Ore","Minuti","Secondi",""],labelsShort:["Anni","Mesi","Gio","Ore","Min","Sec",""],startText:"Inizio",stopText:"Arresto",resetText:"Ripristina",lapText:"Lap",hideText:"Nascondi",backText:"Indietro",undoText:"Annulla",offText:"Via",onText:"Su",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.ja={setText:"セット",cancelText:"キャンセル",clearText:"クリア",selectedText:"{count} 選択",dateFormat:"yy年mm月dd日",dayNames:["日","月","火","水","木","金","土"],dayNamesShort:["日","月","火","水","木","金","土"],dayNamesMin:["日","月","火","水","木","金","土"],dayText:"日",hourText:"時",minuteText:"分",monthNames:["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],monthNamesShort:["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],monthText:"月",secText:"秒",timeFormat:"HH:ii",yearText:"年",nowText:"今",pmText:"午後",amText:"午前",yearSuffix:"年",monthSuffix:"月",daySuffix:"日",todayText:"今日",dateText:"日付",timeText:"時間",closeText:"クローズ",allDayText:"終日",noEventsText:"イベントはありません",eventText:"イベント",eventsText:"イベント",moreEventsText:"他 {count} 件",fromText:"開始",toText:"終わり",wholeText:"全数",fractionText:"分数",unitText:"単位",labels:["年間","月間","日間","時間","分","秒",""],labelsShort:["年間","月間","日間","時間","分","秒",""],startText:"開始",stopText:"停止",resetText:"リセット",lapText:"ラップ",hideText:"隠す",backText:"バック",undoText:"アンドゥ"},Z.i18n.ko={setText:"설정",cancelText:"취소",clearText:"삭제",selectedText:"{count} 선택된",dateFormat:"yy년mm월dd일",dayNames:["일요일","월요일","화요일","수요일","목요일","금요일","토요일"],dayNamesShort:["일","월","화","수","목","금","토"],dayNamesMin:["일","월","화","수","목","금","토"],dayText:"일",delimiter:"-",hourText:"시간",minuteText:"분",monthNames:["1월","2월","3월","4월","5월","6월","7월","8월","9월","10월","11월","12월"],monthNamesShort:["1월","2월","3월","4월","5월","6월","7월","8월","9월","10월","11월","12월"],monthText:"달",secText:"초",timeFormat:"H:ii",yearText:"년",nowText:"지금",pmText:"오후",amText:"오전",yearSuffix:"년",monthSuffix:"월",daySuffix:"일",firstDay:0,dateText:"날짜",timeText:"시간",todayText:"오늘",prevMonthText:"이전 달",nextMonthText:"다음 달",prevYearText:"이전 년",nextYearText:"다음 년",closeText:"닫기",eventText:"이벤트",eventsText:"이벤트",allDayText:"종일",noEventsText:"이벤트 없음",moreEventsText:"{count}개 더보기",fromText:"시작",toText:"종료",wholeText:"정수",fractionText:"분수",unitText:"단위",labels:["년","달","일","시간","분","초",""],labelsShort:["년","달","일","시간","분","초",""],startText:"시작",stopText:"중지 ",resetText:"초기화",lapText:"기록",hideText:"숨는 장소",backText:"뒤로",undoText:"실행취소",offText:"끔",onText:"켬",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.lt={setText:"OK",cancelText:"Atšaukti",clearText:"Išvalyti",selectedText:"Pasirinktas {count}",selectedPluralText:"Pasirinkti {count}",dateFormat:"yy-mm-dd",dayNames:["Sekmadienis","Pirmadienis","Antradienis","Trečiadienis","Ketvirtadienis","Penktadienis","Šeštadienis"],dayNamesShort:["S","Pr","A","T","K","Pn","Š"],dayNamesMin:["S","Pr","A","T","K","Pn","Š"],dayText:"Diena",hourText:"Valanda",minuteText:"Minutes",monthNames:["Sausis","Vasaris","Kovas","Balandis","Gegužė","Birželis","Liepa","Rugpjūtis","Rugsėjis","Spalis","Lapkritis","Gruodis"],monthNamesShort:["Sau","Vas","Kov","Bal","Geg","Bir","Lie","Rugp","Rugs","Spa","Lap","Gruo"],monthText:"Mėnuo",secText:"Sekundes",amText:"am",pmText:"pm",timeFormat:"HH:ii",yearText:"Metai",nowText:"Dabar",todayText:"Šiandien",firstDay:1,dateText:"Data",timeText:"Laikas",closeText:"Uždaryti",allDayText:"Visą dieną",noEventsText:"Nėra įvykių",eventText:"Įvykių",eventsText:"Įvykiai",moreEventsText:"Dar {count}",fromText:"Nuo",toText:"Iki",wholeText:"Visas",fractionText:"Frakcija",unitText:"Vienetas",labels:["Metai","Mėnesiai","Dienos","Valandos","Minutes","Sekundes",""],labelsShort:["m","mėn.","d","h","min","s",""],startText:"Pradėti",stopText:"Sustabdyti",resetText:"Išnaujo",lapText:"Ratas",hideText:"Slėpti",backText:"Atgal",undoText:"Anuliuoti",offText:"Išj.",onText:"Įj.",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.nl={setText:"Instellen",cancelText:"Annuleren",clearText:"Leegmaken",selectedText:"{count} gekozen",dateFormat:"dd-mm-yy",dayNames:["Zondag","Maandag","Dinsdag","Woensdag","Donderdag","Vrijdag","Zaterdag"],dayNamesShort:["zo","ma","di","wo","do","vr","za"],dayNamesMin:["z","m","d","w","d","v","z"],dayText:"Dag",hourText:"Uur",minuteText:"Minuten",monthNames:["januari","februari","maart","april","mei","juni","juli","augustus","september","oktober","november","december"],monthNamesShort:["jan","feb","mrt","apr","mei","jun","jul","aug","sep","okt","nov","dec"],monthText:"Maand",secText:"Seconden",timeFormat:"HH:ii",yearText:"Jaar",nowText:"Nu",pmText:"pm",amText:"am",todayText:"Vandaag",firstDay:1,dateText:"Datum",timeText:"Tijd",closeText:"Sluiten",allDayText:"Hele dag",noEventsText:"Geen activiteiten",eventText:"Activiteit",eventsText:"Activiteiten",moreEventsText:"nog {count}",fromText:"Start",toText:"Einde",wholeText:"geheel",fractionText:"fractie",unitText:"eenheid",labels:["Jaren","Maanden","Dagen","Uren","Minuten","Seconden",""],labelsShort:["j","m","d","u","min","sec",""],startText:"Start",stopText:"Stop",resetText:"Reset",lapText:"Ronde",hideText:"Verbergen",backText:"Terug",undoText:"Onged. maken",offText:"Uit",onText:"Aan",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.no={setText:"OK",cancelText:"Avbryt",clearText:"Tømme",selectedText:"{count} valgt",dateFormat:"dd.mm.yy",dayNames:["Søndag","Mandag","Tirsdag","Onsdag","Torsdag","Fredag","Lørdag"],dayNamesShort:["Sø","Ma","Ti","On","To","Fr","Lø"],dayNamesMin:["S","M","T","O","T","F","L"],dayText:"Dag",delimiter:".",hourText:"Time",minuteText:"Minutt",monthNames:["Januar","Februar","Mars","April","Mai","Juni","Juli","August","September","Oktober","November","Desember"],monthNamesShort:["Jan","Feb","Mar","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Des"],monthText:"Måned",secText:"Sekund",timeFormat:"HH:ii",yearText:"År",nowText:"Nå",pmText:"pm",amText:"am",todayText:"I dag",firstDay:1,dateText:"Dato",timeText:"Tid",closeText:"Lukk",allDayText:"Hele dagen",noEventsText:"Ingen hendelser",eventText:"Hendelse",eventsText:"Hendelser",moreEventsText:"{count} mere",fromText:"Start",toText:"End",wholeText:"Hele",fractionText:"Fraksjon",unitText:"Enhet",labels:["År","Måneder","Dager","Timer","Minutter","Sekunder",""],labelsShort:["År","Mån","Dag","Time","Min","Sek",""],startText:"Start",stopText:"Stopp",resetText:"Tilbakestille",lapText:"Runde",hideText:"Skjul",backText:"Tilbake",undoText:"Angre",offText:"Av",onText:"På",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.pl={setText:"Zestaw",cancelText:"Anuluj",clearText:"Oczyścić",selectedText:"Wybór: {count}",dateFormat:"yy-mm-dd",dayNames:["Niedziela","Poniedziałek","Wtorek","Środa","Czwartek","Piątek","Sobota"],dayNamesShort:["Niedz.","Pon.","Wt.","Śr.","Czw.","Pt.","Sob."],dayNamesMin:["N","P","W","Ś","C","P","S"],dayText:"Dzień",hourText:"Godziny",minuteText:"Minuty",monthNames:["Styczeń","Luty","Marzec","Kwiecień","Maj","Czerwiec","Lipiec","Sierpień","Wrzesień","Październik","Listopad","Grudzień"],monthNamesShort:["Sty","Lut","Mar","Kwi","Maj","Cze","Lip","Sie","Wrz","Paź","Lis","Gru"],monthText:"Miesiąc",secText:"Sekundy",timeFormat:"HH:ii",yearText:"Rok",nowText:"Teraz",amText:"am",pmText:"pm",todayText:"Dzisiaj",firstDay:1,dateText:"Data",timeText:"Czas",closeText:"Zakończenie",allDayText:"Cały dzień",noEventsText:"Brak wydarzeń",eventText:"Wydarzeń",eventsText:"Wydarzenia",moreEventsText:"Jeszcze {count}",fromText:"Rozpoczęcie",toText:"Koniec",wholeText:"Cały",fractionText:"Ułamek",unitText:"Jednostka",labels:["Lata","Miesiąc","Dni","Godziny","Minuty","Sekundy",""],labelsShort:["R","M","Dz","Godz","Min","Sek",""],startText:"Rozpoczęcie",stopText:"Zatrzymać",resetText:"Zresetować",lapText:"Zakładka",hideText:"Ukryć",backText:"Wróć",undoText:"Cofnij",offText:"Wył",onText:"Wł",decimalSeparator:",",thousandsSeparator:" "},Z.i18n["pt-BR"]={setText:"Selecionar",cancelText:"Cancelar",clearText:"Claro",selectedText:"{count} selecionado",selectedPluralText:"{count} selecionados",dateFormat:"dd/mm/yy",dayNames:["Domingo","Segunda-feira","Terça-feira","Quarta-feira","Quinta-feira","Sexta-feira","Sábado"],dayNamesShort:["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"],dayNamesMin:["D","S","T","Q","Q","S","S"],dayText:"Dia",hourText:"Hora",minuteText:"Minutos",monthNames:["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"],monthNamesShort:["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"],monthText:"Mês",secText:"Segundo",timeFormat:"HH:ii",yearText:"Ano",nowText:"Agora",pmText:"pm",amText:"am",todayText:"Hoje",dateText:"Data",timeText:"Tempo",closeText:"Fechar",allDayText:"Dia inteiro",noEventsText:"Nenhum evento",eventText:"Evento",eventsText:"Eventos",moreEventsText:"Mais {count}",fromText:"In&iacute;cio",toText:"Fim",wholeText:"Inteiro",fractionText:"Fração",unitText:"Unidade",labels:["Anos","Meses","Dias","Horas","Minutos","Segundos",""],labelsShort:["Ano","M&ecirc;s","Dia","Hora","Min","Seg",""],startText:"Começar",stopText:"Pare",resetText:"Reinicializar",lapText:"Lap",hideText:"Esconder",backText:"Anterior",undoText:"Desfazer",offText:"Desl",onText:"Lig",decimalSeparator:",",thousandsSeparator:" "},Z.i18n["pt-PT"]={setText:"Seleccionar",cancelText:"Cancelar",clearText:"Claro",selectedText:"{count} selecionado",selectedPluralText:"{count} selecionados",dateFormat:"dd-mm-yy",dayNames:["Domingo","Segunda-feira","Terça-feira","Quarta-feira","Quinta-feira","Sexta-feira","Sábado"],dayNamesShort:["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"],dayNamesMin:["D","S","T","Q","Q","S","S"],dayText:"Dia",hourText:"Horas",minuteText:"Minutos",monthNames:["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"],monthNamesShort:["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"],monthText:"Mês",secText:"Segundo",timeFormat:"HH:ii",yearText:"Ano",nowText:"Actualizar",pmText:"pm",amText:"am",todayText:"Hoy",firstDay:1,dateText:"Data",timeText:"Tempo",closeText:"Fechar",allDayText:"Todo o dia",noEventsText:"Nenhum evento",eventText:"Evento",eventsText:"Eventos",moreEventsText:"mais {count}",fromText:"Início",toText:"Fim",wholeText:"Inteiro",fractionText:"Fracção",unitText:"Unidade",labels:["Anos","Meses","Dias","Horas","Minutos","Segundos",""],labelsShort:["Ano","Mês","Dia","Hora","Min","Seg",""],startText:"Começar",stopText:"Parar",resetText:"Reinicializar",lapText:"Lap",hideText:"Esconder",backText:"Anterior",undoText:"Anular",offText:"Desl",onText:"Lig",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.ro={setText:"Setare",cancelText:"Anulare",clearText:"Ştergere",selectedText:"{count} selectat",selectedPluralText:"{count} selectate",dateFormat:"dd.mm.yy",dayNames:["Duminică","Luni","Marți","Miercuri","Joi","Vineri","Sâmbătă"],dayNamesShort:["Du","Lu","Ma","Mi","Jo","Vi","Sâ"],dayNamesMin:["D","L","M","M","J","V","S"],dayText:" Ziua",delimiter:".",hourText:" Ore ",minuteText:"Minute",monthNames:["Ianuarie","Februarie","Martie","Aprilie","Mai","Iunie","Iulie","August","Septembrie","Octombrie","Noiembrie","Decembrie"],monthNamesShort:["Ian.","Feb.","Mar.","Apr.","Mai","Iun.","Iul.","Aug.","Sept.","Oct.","Nov.","Dec."],monthText:"Luna",secText:"Secunde",timeFormat:"HH:ii",yearText:"Anul",nowText:"Acum",amText:"am",pmText:"pm",todayText:"Astăzi",prevMonthText:"Luna anterioară",nextMonthText:"Luna următoare",prevYearText:"Anul anterior",nextYearText:"Anul următor",eventText:"Eveniment",eventsText:"Evenimente",allDayText:"Toată ziua",noEventsText:"Niciun eveniment",moreEventsText:"Încă unul",moreEventsPluralText:"Încă {count}",firstDay:1,dateText:"Data",timeText:"Ora",closeText:"Închidere",fromText:"Start",toText:"Final",wholeText:"Complet",fractionText:"Parţial",unitText:"Unitate",labels:["Ani","Luni","Zile","Ore","Minute","Secunde",""],labelsShort:["Ani","Luni","Zile","Ore","Min.","Sec.",""],startText:"Start",stopText:"Stop",resetText:"Resetare",lapText:"Tură",hideText:"Ascundere",backText:"Înapoi",undoText:"Anulează",offText:"Nu",onText:"Da",decimalSeparator:",",thousandsSeparator:" "},Z.i18n["ru-UA"]={setText:"Установить",cancelText:"Отменить",clearText:"Очиститьr",selectedText:"{count} Вібрать",dateFormat:"dd.mm.yy",dayNames:["воскресенье","понедельник","вторник","среда","четверг","пятница","суббота"],dayNamesShort:["вс","пн","вт","ср","чт","пт","сб"],dayNamesMin:["в","п","в","с","ч","п","с"],dayText:"День",delimiter:".",hourText:"Часы",minuteText:"Минуты",monthNames:["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],monthNamesShort:["Янв.","Февр.","Март","Апр.","Май","Июнь","Июль","Авг.","Сент.","Окт.","Нояб.","Дек."],monthText:"Месяцы",secText:"Сикунды",timeFormat:"HH:ii",yearText:"Год",nowText:"Сейчас",amText:"am",pmText:"pm",todayText:"Cегодня",firstDay:1,dateText:"Дата",timeText:"Время",closeText:"Закрыть",allDayText:"Весь день",noEventsText:"Нет событий",eventText:"Мероприятия",eventsText:"Мероприятия",moreEventsText:"Ещё {count}",fromText:"Начало",toText:"Конец",wholeText:"Весь",fractionText:"Часть",unitText:"Единица",labels:["Годы"," Месяцы "," Дни "," Часы "," Минуты "," Секунды",""],labelsShort:["Год","Мес.","Дн.","Ч.","Мин.","Сек.",""],startText:"Старт",stopText:"Стоп",resetText:" Сброс ",lapText:" Этап ",hideText:" Скрыть ",backText:"назад",undoText:"ОтменитЬ",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:" "},Z.i18n["ru-RU"]=Z.i18n.ru={setText:"Установить",cancelText:"Отмена",clearText:"Очистить",selectedText:"{count} Выбрать",dateFormat:"dd.mm.yy",dayNames:["воскресенье","понедельник","вторник","среда","четверг","пятница","суббота"],dayNamesShort:["вс","пн","вт","ср","чт","пт","сб"],dayNamesMin:["в","п","в","с","ч","п","с"],dayText:"День",delimiter:".",hourText:"Час",minuteText:"Минут",monthNames:["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],monthNamesShort:["Янв","Фев","Мар","Апр","Май","Июн","Июл","Авг","Сен","Окт","Ноя","Дек"],monthText:"Месяц",secText:"Секунд",timeFormat:"HH:ii",yearText:"Год",nowText:"Сейчас",amText:"am",pmText:"pm",todayText:"Cегодня",firstDay:1,dateText:"Дата",timeText:"Время",closeText:"Закрыть",allDayText:"Весь день",noEventsText:"Нет событий",eventText:"Мероприятия",eventsText:"Мероприятия",moreEventsText:"Ещё {count}",fromText:"Начало",toText:"Конец",wholeText:"Целое",fractionText:"Дробное",unitText:"Единица",labels:["Лет","Месяцев","Дней","Часов","Минут","Секунд",""],labelsShort:["Лет","Мес","Дн","Час","Мин","Сек",""],startText:"Старт",stopText:"Стоп",resetText:"Сбросить",lapText:"Круг",hideText:"Скрыть",backText:"назад",undoText:"ОтменитЬ",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.sk={setText:"Zadaj",cancelText:"Zrušiť",clearText:"Vymazať",selectedText:"Označený: {count}",dateFormat:"d.m.yy",dayNames:["Nedeľa","Pondelok","Utorok","Streda","Štvrtok","Piatok","Sobota"],dayNamesShort:["Ne","Po","Ut","St","Št","Pi","So"],dayNamesMin:["N","P","U","S","Š","P","S"],dayText:"Ďeň",hourText:"Hodiny",minuteText:"Minúty",monthNames:["Január","Február","Marec","Apríl","Máj","Jún","Júl","August","September","Október","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","Máj","Jún","Júl","Aug","Sep","Okt","Nov","Dec"],monthText:"Mesiac",secText:"Sekundy",timeFormat:"H:ii",yearText:"Rok",nowText:"Teraz",amText:"am",pmText:"pm",todayText:"Dnes",firstDay:1,dateText:"Datum",timeText:"Čas",closeText:"Zavrieť",allDayText:"Celý deň",noEventsText:"Žiadne udalosti",eventText:"Udalostí",eventsText:"Udalosti",moreEventsText:"{count} ďalšia",moreEventsPluralText:"{count} ďalšie",fromText:"Začiatok",toText:"Koniec",wholeText:"Celý",fractionText:"Časť",unitText:"Jednotka",labels:["Roky","Mesiace","Dni","Hodiny","Minúty","Sekundy",""],labelsShort:["Rok","Mes","Dni","Hod","Min","Sec",""],startText:"Start",stopText:"Stop",resetText:"Resetovať",lapText:"Etapa",hideText:"Schovať",backText:"Späť",undoText:"Späť",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.sr={setText:"Постави",cancelText:"Откажи",clearText:"Обриши",selectedText:"{count} изабрана",dateFormat:"dd.mm.yy",dayNames:["Недеља","Понедељак","Уторак","Среда","Четвртак","Петак","Субота"],dayNamesShort:["Нед","Пон","Уто","Сре","Чет","Пет","Суб"],dayNamesMin:["Не","По","Ут","Ср","Че","Пе","Су"],dayText:"Дан",delimiter:".",hourText:"Час",minuteText:"Минут",monthNames:["Јануар","Фебруар","Март","Април","Мај","Јун","Јул","Август","Септембар","Октобар","Новембар","Децембар"],monthNamesShort:["Јан","Феб","Мар","Апр","Мај","Јун","Јул","Авг","Сеп","Окт","Нов","Дец"],monthText:"месец",secText:"Секунд",timeFormat:"H:ii",yearText:"година",nowText:"сада",pmText:"pm",amText:"am",firstDay:1,dateText:"Датум",timeText:"време",todayText:"Данас",prevMonthText:"Претходни мјесец",nextMonthText:"Следећег месеца",prevYearText:"Претходна године",nextYearText:"Следеће године",closeText:"Затвори",eventText:"Догађај",eventsText:"Догађаји",allDayText:"Цео дан",noEventsText:"Нема догађаја",moreEventsText:"Још {count}",fromText:"Од",toText:"До",wholeText:"цео",fractionText:"Фракција",unitText:"единица",labels:["Године","Месеци","Дана","Сати","Минута","Секунди",""],labelsShort:["Год","Мес","Дана","Сати","Мину","Секу",""],startText:"Започни",stopText:"Стоп",resetText:"Ресетуј",lapText:"Круг",hideText:"Сакрити",backText:"Повратак",undoText:"Опозови",offText:"нe",onText:"да",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.sv={setText:"OK",cancelText:"Avbryt",clearText:"Klara",selectedText:"{count} vald",dateFormat:"yy-mm-dd",dayNames:["Söndag","Måndag","Tisdag","Onsdag","Torsdag","Fredag","Lördag"],dayNamesShort:["Sö","Må","Ti","On","To","Fr","Lö"],dayNamesMin:["S","M","T","O","T","F","L"],dayText:"Dag",hourText:"Timme",minuteText:"Minut",monthNames:["Januari","Februari","Mars","April","Maj","Juni","Juli","Augusti","September","Oktober","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","Maj","Jun","Jul","Aug","Sep","Okt","Nov","Dec"],monthText:"Månad",secText:"Sekund",timeFormat:"HH:ii",yearText:"År",nowText:"Nu",pmText:"pm",amText:"am",todayText:"I dag",firstDay:1,dateText:"Datum",timeText:"Tid",closeText:"Stäng",allDayText:"Heldag",noEventsText:"Inga aktiviteter",eventText:"Händelse",eventsText:"Händelser",moreEventsText:"{count} till",fromText:"Start",toText:"Slut",wholeText:"Hela",fractionText:"Bråk",unitText:"Enhet",labels:["År","Månader","Dagar","Timmar","Minuter","Sekunder",""],labelsShort:["År","Mån","Dag","Tim","Min","Sek",""],startText:"Start",stopText:"Stopp",resetText:"Återställ",lapText:"Varv",hideText:"Dölj",backText:"Tillbaka",undoText:"Ångra",offText:"Av",onText:"På"},Z.i18n.th={setText:"ตั้งค่า",cancelText:"ยกเลิก",clearText:"ล้าง",selectedText:"{count} เลือก",dateFormat:"dd/mm/yy",dayNames:["อาทิตย์","จันทร์","อังคาร","พุธ","พฤหัสบดี","ศุกร์","เสาร์"],dayNamesShort:["อา.","จ.","อ.","พ.","พฤ.","ศ.","ส."],dayNamesMin:["อา.","จ.","อ.","พ.","พฤ.","ศ.","ส."],dayText:"วัน",delimiter:".",hourText:"ชั่วโมง",minuteText:"นาที",monthNames:["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"],monthNamesShort:["ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."],monthText:"เดือน",secText:"วินาที",timeFormat:"HH:ii",yearText:"ปี",nowText:"ตอนนี้",pmText:"pm",amText:"am",firstDay:0,dateText:"วัน",timeText:"เวลา",today:"วันนี้",prevMonthText:"เดือนก่อนหน้า",nextMonthText:"เดือนถัดไป",prevYearText:"ปีก่อนหน้า",nextYearText:"ปีถัดไป",closeText:"ปิด",eventText:"เหตุการณ์",eventsText:"เหตุการณ์",allDayText:"ตลอดวัน",noEventsText:"ไม่มีกิจกรรม",moreEventsText:"อีก {count} กิจกรรม",fromText:"จาก",toText:"ถึง",wholeText:"ทั้งหมด",fractionText:"เศษส่วน",unitText:"หน่วย",labels:["ปี","เดือน","วัน","ชั่วโมง","นาที","วินาที",""],labelsShort:["ปี","เดือน","วัน","ชั่วโมง","นาที","วินาที",""],startText:"เริ่ม",stopText:"หยุด",resetText:"รีเซ็ต",lapText:"รอบ",hideText:"ซ่อน",backText:"ย้อนกลับ",undoText:"เลิกทา",offText:"ปิด",onText:"เปิด",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.tr={setText:"Seç",cancelText:"İptal",clearText:"Temizleyin",selectedText:"{count} seçilmiş",dateFormat:"dd.mm.yy",dayNames:["Pazar","Pazartesi","Salı","Çarşamba","Perşembe","Cuma","Cumartesi"],dayNamesShort:["Paz","Pzt","Sal","Çar","Per","Cum","Cmt"],dayNamesMin:["P","P","S","Ç","P","C","C"],dayText:"Gün",delimiter:".",hourText:"Saat",minuteText:"Dakika",monthNames:["Ocak","Şubat","Mart","Nisan","Mayıs","Haziran","Temmuz","Ağustos","Eylül","Ekim","Kasım","Aralık"],monthNamesShort:["Oca","Şub","Mar","Nis","May","Haz","Tem","Ağu","Eyl","Eki","Kas","Ara"],monthText:"Ay",secText:"Saniye",timeFormat:"HH:ii",yearText:"Yıl",nowText:"Şimdi",pmText:"pm",amText:"am",todayText:"Bugün",firstDay:1,dateText:"Tarih",timeText:"Zaman",closeText:"Kapatmak",allDayText:"Tüm gün",noEventsText:"Etkinlik Yok",eventText:"Etkinlik",eventsText:"Etkinlikler",moreEventsText:"{count} tane daha",fromText:"Başla",toText:"Son",wholeText:"Tam",fractionText:"Kesir",unitText:"Birim",labels:["Yıl","Ay","Gün","Saat","Dakika","Saniye",""],labelsShort:["Yıl","Ay","Gün","Sa","Dak","Sn",""],startText:"Başla",stopText:"Durdur",resetText:"Sıfırla",lapText:"Tur",hideText:"Gizle",backText:"Geri",undoText:"Geri Al",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:"."},Z.i18n.ua={setText:"встановити",cancelText:"відміна",clearText:"очистити",selectedText:"{count} вибрані",dateFormat:"dd.mm.yy",dayNames:["неділя","понеділок","вівторок","середа","четвер","п’ятниця","субота"],dayNamesShort:["нед","пнд","вів","срд","чтв","птн","сбт"],dayNamesMin:["Нд","Пн","Вт","Ср","Чт","Пт","Сб"],dayText:"День",delimiter:".",hourText:"година",minuteText:"хвилина",monthNames:["Січень","Лютий","Березень","Квітень","Травень","Червень","Липень","Серпень","Вересень","Жовтень","Листопад","Грудень"],monthNamesShort:["Січ","Лют","Бер","Кві","Тра","Чер","Лип","Сер","Вер","Жов","Лис","Гру"],monthText:"Місяць",secText:"Секунд",timeFormat:"H:ii",yearText:"Рік",nowText:"Зараз",pmText:"pm",amText:"am",firstDay:1,dateText:"дата",timeText:"Час",todayText:"Сьогодні",prevMonthText:"Попередній місяць",nextMonthText:"Наступного місяця",prevYearText:"Попередній рік",nextYearText:"Наступного року",closeText:"Закрити",eventText:"подія",eventsText:"події",allDayText:"Увесь день",noEventsText:"Жодної події",moreEventsText:"та ще {count}",fromText:"від",toText:"кінець",wholeText:"всі",fractionText:"фракція",unitText:"одиниця",labels:["Рік","Місяць","День","година","хвилина","Секунд",""],labelsShort:["Рік","Місяць","День","година","хвилина","Секунд",""],startText:"Початок",stopText:"СТОП",resetText:"скинути",lapText:"коло",hideText:"сховати",backText:"назад",undoText:"відмінити",offText:"Вимикати",onText:"Увімкнути",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.vi={setText:"Đặt",cancelText:"Hủy bò",clearText:"Xóa",selectedText:"{count} chọn",dateFormat:"dd/mm/yy",dayNames:["Chủ Nhật","Thứ Hai","Thứ Ba","Thứ Tư","Thứ Năm","Thứ Sáu","Thứ Bảy"],dayNamesShort:["CN","T2","T3","T4","T5","T6","T7"],dayNamesMin:["CN","T2","T3","T4","T5","T6","T7"],dayText:"",delimiter:"/",hourText:"Giờ",minuteText:"Phút",monthNames:["Tháng Một","Tháng Hai","Tháng Ba","Tháng Tư","Tháng Năm","Tháng Sáu","Tháng Bảy","Tháng Tám","Tháng Chín","Tháng Mười","Tháng Mười Một","Tháng Mười Hai"],monthNamesShort:["Tháng 1","Tháng 2","Tháng 3","Tháng 4","Tháng 5","Tháng 6","Tháng 7","Tháng 8","Tháng 9","Tháng 10","Tháng 11","Tháng 12"],monthText:"Tháng",secText:"Giây",timeFormat:"H:ii",yearText:"Năm",nowText:"Bây giờ",pmText:"pm",amText:"am",firstDay:0,dateText:"Ngày",timeText:"Hồi",todayText:"Hôm nay",prevMonthText:"Tháng trước",nextMonthText:"Tháng tới",prevYearText:"Măm trước",nextYearText:"Năm tới",closeText:"Đóng",eventText:"Sự kiện",eventsText:"Sự kiện",allDayText:"Cả ngày",noEventsText:"Không có sự kiện",moreEventsText:"{count} thẻ khác",fromText:"Từ",toText:"Tới",wholeText:"Toàn thể",fractionText:"Phân số",unitText:"đơn vị",labels:["Năm","Tháng","Ngày","Giờ","Phút","Giây",""],labelsShort:["Năm","Tháng","Ngày","Giờ","Phút","Giây",""],startText:"Bắt đầu",stopText:"Dừng",resetText:"Đặt lại",lapText:"Vòng",hideText:"Giấu",backText:"Quay lại",undoText:"Hoàn tác",offText:"Tất",onText:"Bật",decimalSeparator:",",thousandsSeparator:" "},Z.i18n.zh={setText:"确定",cancelText:"取消",clearText:"明确",selectedText:"{count} 选",dateFormat:"yy年mm月d日",dayNames:["周日","周一","周二","周三","周四","周五","周六"],dayNamesShort:["日","一","二","三","四","五","六"],dayNamesMin:["日","一","二","三","四","五","六"],dayText:"日",hourText:"时",minuteText:"分",monthNames:["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],monthNamesShort:["一","二","三","四","五","六","七","八","九","十","十一","十二"],monthText:"月",secText:"秒",timeFormat:"HH:ii",yearText:"年",nowText:"当前",pmText:"下午",amText:"上午",yearSuffix:"年",monthSuffix:"月",daySuffix:"日",todayText:"今天",dateText:"日",timeText:"时间",closeText:"关闭",allDayText:"全天",noEventsText:"无事件",eventText:"活动",eventsText:"活动",moreEventsText:"他 {count} 件",fromText:"开始时间",toText:"结束时间",wholeText:"合计",fractionText:"分数",unitText:"单位",labels:["年","月","日","小时","分钟","秒",""],labelsShort:["年","月","日","点","分","秒",""],startText:"开始",stopText:"停止",resetText:"重置",lapText:"圈",hideText:"隐藏",backText:"返回",undoText:"复原",offText:"关闭",onText:"开启",decimalSeparator:",",thousandsSeparator:" "};var hs=Z.themes;hs.frame.bootstrap={disabledClass:"disabled",selectedClass:"btn-primary",selectedTabClass:"active",tabLink:!0,todayClass:"text-primary mbsc-cal-today",onMarkupInserted:function(e){var t=pa(e.target),a=pa(".mbsc-cal-tabs",t);pa(".mbsc-fr-popup",t).addClass("popover"),pa(".mbsc-fr-w",t).addClass("popover-content"),pa(".mbsc-fr-hdr",t).addClass("popover-title popover-header"),pa(".mbsc-fr-arr-i",t).addClass("popover"),pa(".mbsc-fr-arr",t).addClass("arrow"),pa(".mbsc-fr-btn",t).addClass("btn btn-default btn-secondary"),pa(".mbsc-fr-btn-s .mbsc-fr-btn",t).removeClass("btn-default btn-secondary").addClass("btn btn-primary"),a.addClass("nav nav-tabs"),a.find(".mbsc-cal-tab").addClass("nav-item"),a.find("a").addClass("nav-link"),a.find(".mbsc-cal-tab.active .nav-link").addClass("active"),pa(".mbsc-cal-picker",t).addClass("popover"),pa(".mbsc-range-btn",t).addClass("btn btn-sm btn-small btn-default"),pa(".mbsc-np-btn",t).addClass("btn btn-default"),pa(".mbsc-sel-filter-cont",t).removeClass("mbsc-input"),pa(".mbsc-sel-filter-input",t).addClass("form-control")},onTabChange:function(e,t){pa(".mbsc-cal-tabs .nav-link",t._markup).removeClass("active"),pa(".mbsc-cal-tab.active .nav-link",t._markup).addClass("active")},onPosition:function(e){setTimeout(function(){pa(".mbsc-fr-bubble-top, .mbsc-fr-bubble-top .mbsc-fr-arr-i",e.target).removeClass("bottom bs-popover-bottom").addClass("top bs-popover-top"),pa(".mbsc-fr-bubble-bottom, .mbsc-fr-bubble-bottom .mbsc-fr-arr-i",e.target).removeClass("top bs-popover-top").addClass("bottom  bs-popover-bottom")},10)}},hs.scroller.bootstrap=va({},hs.frame.bootstrap,{dateDisplay:"Mddyy",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5",btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5 btn-light",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5 btn-light",selectedLineHeight:!0,onEventBubbleShow:function(e){var t=pa(e.eventList);pa(".mbsc-cal-event-list",t).addClass("list-group"),pa(".mbsc-cal-event",t).addClass("list-group-item")}}),hs.navigation.bootstrap={wrapperClass:"popover panel panel-default",groupClass:"btn-group",activeClass:"btn-primary",disabledClass:"disabled",itemClass:"btn btn-default"},hs.form.bootstrap={};var fs=Z.themes;function ps(e,t){var a=ua(t,"X",!0),s=ua(t,"Y",!0),n=e.offset(),i=a-n.left,o=s-n.top,r=Math.max(i,e[0].offsetWidth-i),l=Math.max(o,e[0].offsetHeight-o),c=2*Math.sqrt(Math.pow(r,2)+Math.pow(l,2));bs(gs),gs=pa('<span class="mbsc-ripple"></span>').css({width:c,height:c,top:s-n.top-c/2,left:a-n.left-c/2}).appendTo(e),setTimeout(function(){gs.addClass("mbsc-ripple-scaled mbsc-ripple-visible")},10)}function bs(e){setTimeout(function(){e&&(e.removeClass("mbsc-ripple-visible"),setTimeout(function(){e.remove()},2e3))},100)}function vs(e,t,a,s){var n,i;e.off(".mbsc-ripple").on("touchstart.mbsc-ripple mousedown.mbsc-ripple",t,function(e){ga(e,this)&&(n=ua(e,"X"),i=ua(e,"Y"),(xs=pa(this)).hasClass(a)||xs.hasClass(s)?xs=null:ps(xs,e))}).on("touchmove.mbsc-ripple mousemove.mbsc-ripple",t,function(e){(xs&&9<Math.abs(ua(e,"X")-n)||9<Math.abs(ua(e,"Y")-i))&&(bs(gs),xs=null)}).on("touchend.mbsc-ripple touchcancel.mbsc-ripple mouseleave.mbsc-ripple mouseup.mbsc-ripple",t,function(){xs&&(setTimeout(function(){bs(gs)},100),xs=null)})}fs.frame.ios={display:"bottom",headerText:!1,btnWidth:!1,deleteIcon:"ios-backspace",scroll3d:"wp"!=p&&("android"!=p||7<v)},fs.scroller.ios=va({},fs.frame.ios,{rows:5,height:34,minWidth:55,selectedLineHeight:!0,selectedLineBorder:1,showLabel:!1,useShortLabels:!0,btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5",checkIcon:"ion-ios7-checkmark-empty",filterClearIcon:"ion-close-circled",dateDisplay:"MMdyy",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5"}),fs.listview.ios={leftArrowClass:"mbsc-ic-ion-ios7-arrow-back",rightArrowClass:"mbsc-ic-ion-ios7-arrow-forward"},fs.form.ios={};var xs,gs,Ts=Z.themes;Ts.frame.material={headerText:!1,btnWidth:!1,deleteIcon:"material-backspace",onMarkupReady:function(e){vs(pa(e.target),".mbsc-fr-btn-e","mbsc-disabled","mbsc-fr-btn-nhl")}},Ts.scroller.material=va({},Ts.frame.material,{showLabel:!1,selectedLineBorder:2,weekDays:"min",icon:{filled:"material-star",empty:"material-star-outline"},checkIcon:"material-check",btnPlusClass:"mbsc-ic mbsc-ic-material-keyboard-arrow-down",btnMinusClass:"mbsc-ic mbsc-ic-material-keyboard-arrow-up",btnCalPrevClass:"mbsc-ic mbsc-ic-material-keyboard-arrow-left",btnCalNextClass:"mbsc-ic mbsc-ic-material-keyboard-arrow-right"}),Ts.listview.material={leftArrowClass:"mbsc-ic-material-keyboard-arrow-left",rightArrowClass:"mbsc-ic-material-keyboard-arrow-right",onItemActivate:function(e){ps(pa(e.target),e.domEvent)},onItemDeactivate:function(){bs(gs)},onSlideStart:function(e){pa(".mbsc-ripple",e.target).remove()},onSortStart:function(e){pa(".mbsc-ripple",e.target).remove()}},Ts.navigation.material={onInit:function(){vs(pa(this),".mbsc-ms-item.mbsc-btn-e","mbsc-disabled","mbsc-btn-nhl")},onMarkupInit:function(){pa(".mbsc-ripple",this).remove()},onDestroy:function(){pa(this).off(".mbsc-ripple")}},Ts.form.material={addRipple:function(e,t){ps(e,t)},removeRipple:function(){bs(gs)}};var ys=Z.themes;ys.frame.windows={headerText:!1,deleteIcon:"backspace4",btnReverse:!0},ys.scroller.windows=va({},ys.frame.windows,{rows:6,minWidth:88,height:44,btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5",checkIcon:"material-check",dateDisplay:"MMdyy",showLabel:!1,showScrollArrows:!0,btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5",dayNamesShort:["Su","Mo","Tu","We","Th","Fr","Sa"],useShortLabels:!0}),ys.form.windows={},Z.customTheme("ios-dark","ios"),Z.customTheme("material-dark","material"),Z.customTheme("mobiscroll-dark","mobiscroll"),Z.customTheme("windows-dark","windows");var _s=Z.themes,ws="mobiscroll";return"android"==p?ws="material":"ios"==p?ws="ios":"wp"==p&&(ws="windows"),pa.each(_s.frame,function(e,t){if(ws&&t.baseTheme==ws&&e!=ws+"-dark")return Z.autoTheme=e,!1;e==ws&&(Z.autoTheme=e)}),Z.customTheme("ios-gray","ios"),Z.customTheme("material-indigo","material"),Z.customTheme("mobiscroll-lime","mobiscroll"),Z.customTheme("windows-yellow","windows"),Z});
/* eslint-disable */
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t(require("jquery")):"function"==typeof define&&define.amd?define(["jquery"],t):e.mobiscroll=t(e.jQuery)}(this,function(e){"use strict";function t(e,t,a){var s=e;return"object"===("undefined"==typeof t?"undefined":q(t))?e.each(function(){new t.component(this,t)}):("string"==typeof t&&e.each(function(){var e,n=O.instances[this.id];if(n&&n[t]&&(e=n[t].apply(this,Array.prototype.slice.call(a,1)),void 0!==e))return s=e,!1}),s)}function a(e,a,s){X[e]=function(n){return t(this,R(n,{component:a,preset:s===!1?void 0:e}),arguments)}}function s(){}function n(e){var t,a=[];for(t in e)a.push(e[t]);return a}function i(e){var t,a={};if(e)for(t=0;t<e.length;t++)a[e[t]]=e[t];return a}function o(e){return e-parseFloat(e)>=0}function l(e){return"string"==typeof e}function r(e,t,a){return Math.max(t,Math.min(e,a))}function c(e,t){for(e+="",t=t||2;e.length<t;)e="0"+e;return e}function d(e){"vibrate"in navigator&&navigator.vibrate(e||50)}function u(){ae++,setTimeout(function(){ae--},500)}function m(e,t){if(!t.mbscClick){var a=(e.originalEvent||e).changedTouches[0],s=document.createEvent("MouseEvents");s.initMouseEvent("click",!0,!0,window,1,a.screenX,a.screenY,a.clientX,a.clientY,!1,!1,!1,!1,0,null),s.isMbscTap=!0,s.isIonicTap=!0,se=!0,t.mbscChange=!0,t.mbscClick=!0,t.dispatchEvent(s),se=!1,u(),setTimeout(function(){delete t.mbscClick})}}function f(e,t,a){var s=e.originalEvent||e,n=(a?"page":"client")+t;return s.targetTouches&&s.targetTouches[0]?s.targetTouches[0][n]:s.changedTouches&&s.changedTouches[0]?s.changedTouches[0][n]:e[n]}function h(e,t,a,s,n,i){function o(e){b||(s&&e.preventDefault(),b=this,d=f(e,"X"),h=f(e,"Y"),p=!1,v=new Date)}function l(e){b&&!p&&(Math.abs(f(e,"X")-d)>n||Math.abs(f(e,"Y")-h)>n)&&(p=!0)}function r(e){b&&(i&&new Date-v<100||!p?m(e,e.target):u(),b=!1)}function c(){b=!1}var d,h,b,p,v,g=O.$,y=g(t);n=n||9,e.settings.tap&&y.on("touchstart.mbsc",o).on("touchcancel.mbsc",c).on("touchmove.mbsc",l).on("touchend.mbsc",r),y.on("click.mbsc",function(t){s&&t.preventDefault(),a.call(this,t,e)})}function b(e){if(ae&&!se&&!e.isMbscTap&&("TEXTAREA"!=e.target.nodeName||"mousedown"!=e.type))return e.stopPropagation(),e.preventDefault(),!1}function p(e,t){var a=document.createElement("script"),s="mbscjsonp"+ ++me;window[s]=function(e){a.parentNode.removeChild(a),delete window[s],e&&t(e)},a.src=e+(e.indexOf("?")>=0?"&":"?")+"callback="+s,document.body.appendChild(a)}function v(e,t){var a=new XMLHttpRequest;a.open("GET",e,!0),a.onload=function(){this.status>=200&&this.status<400&&t(JSON.parse(this.response))},a.onerror=function(){},a.send()}function g(e,t,a){"jsonp"==a?p(e,t):v(e,t)}function y(e){var t;for(t in e)if(void 0!==he[e[t]])return!0;return!1}function _(){var e,t=["Webkit","Moz","O","ms"];for(e in t)if(y([t[e]+"Transform"]))return"-"+t[e].toLowerCase()+"-";return""}function x(e,t){if("touchstart"==e.type)ie(t).attr("data-touch","1");else if(ie(t).attr("data-touch"))return ie(t).removeAttr("data-touch"),!1;return!0}function w(e,t){var a,s,n=getComputedStyle(e[0]);return ie.each(["t","webkitT","MozT","OT","msT"],function(e,t){if(void 0!==n[t+"ransform"])return a=n[t+"ransform"],!1}),a=a.split(")")[0].split(", "),s=t?a[13]||a[5]:a[12]||a[4]}function C(e){if(e){if(ye[e])return ye[e];var t=ie('<div style="background-color:'+e+';"></div>').appendTo("body"),a=getComputedStyle(t[0]),s=a.backgroundColor.replace(/rgb|rgba|\(|\)|\s/g,"").split(","),n=.299*s[0]+.587*s[1]+.114*s[2],i=n<130?"#fff":"#000";return t.remove(),ye[e]=i,i}}function T(e,t,a,n,i,o){function l(e){var t;b=ie(this),C=+b.attr("data-step"),v=+b.attr("data-index"),p=!0,i&&e.stopPropagation(),"touchstart"==e.type&&b.closest(".mbsc-no-touch").removeClass("mbsc-no-touch"),"mousedown"==e.type&&e.preventDefault(),"keydown"!=e.type?(_=f(e,"X"),w=f(e,"Y"),t=x(e,this)):t=32===e.keyCode,g||!t||b.hasClass("mbsc-disabled")||(u(v,C,e)&&(b.addClass("mbsc-active"),o&&o.addRipple(b.find(".mbsc-segmented-content"),e)),"mousedown"==e.type&&ie(document).on("mousemove",r).on("mouseup",c))}function r(e){(Math.abs(_-f(e,"X"))>7||Math.abs(w-f(e,"Y"))>7)&&(p=!0,d())}function c(e){"touchend"==e.type&&e.preventDefault(),d(),"mouseup"==e.type&&ie(document).off("mousemove",r).off("mouseup",c)}function d(){g=!1,clearInterval(T),b&&(b.removeClass("mbsc-active"),o&&setTimeout(function(){o.removeRipple()},100))}function u(e,t,a){return g||D(e)||(v=e,C=t,y=a,g=!0,p=!1,setTimeout(m,100)),g}function m(){return b&&b.hasClass("mbsc-disabled")?void d():(!g&&p||(p=!0,t(v,C,y,m)),void(g&&a&&(clearInterval(T),T=setInterval(function(){t(v,C,y)},a))))}function h(){e.off("touchstart mousedown keydown",l).off("touchmove",r).off("touchend touchcancel keyup",c)}var b,p,v,g,y,_,w,C,T,D=n||s;return e.on("touchstart mousedown keydown",l).on("touchmove",r).on("touchend touchcancel keyup",c),{start:u,stop:d,destroy:h}}function D(e,t,a){function s(){r.style.width="100000px",r.style.height="100000px",l.scrollLeft=1e5,l.scrollTop=1e5,u.scrollLeft=1e5,u.scrollTop=1e5}function n(){var e=new Date;c=0,m||(e-f>200&&!l.scrollTop&&!l.scrollLeft&&(f=e,s()),c||(c=ee(n)))}function i(){d||(d=ee(o))}function o(){d=0,s(),t()}var l=void 0,r=void 0,c=void 0,d=void 0,u=void 0,m=void 0,f=0,h=document.createElement("div");return h.innerHTML=Te,h.dir="ltr",u=h.childNodes[1],l=h.childNodes[0],r=l.childNodes[0],e.appendChild(h),l.addEventListener("scroll",i),u.addEventListener("scroll",i),a?a.runOutsideAngular(function(){ee(n)}):ee(n),{detach:function(){e.removeChild(h),m=!0}}}function M(e){return(e+"").replace('"',"___")}function k(e,t,a,s,n,i,o){var l=new Date(e,t,a,s||0,n||0,i||0,o||0);return 23==l.getHours()&&0===(s||0)&&l.setHours(l.getHours()+2),l}function S(e,t,a){if(!t)return null;var s,n,i=de({},je,a),o=function(t){for(var a=0;s+1<e.length&&e.charAt(s+1)==t;)a++,s++;return a},l=function(e,t,a){var s=""+t;if(o(e))for(;s.length<a;)s="0"+s;return s},r=function(e,t,a,s){return o(e)?s[t]:a[t]},c="",d=!1;for(s=0;s<e.length;s++)if(d)"'"!=e.charAt(s)||o("'")?c+=e.charAt(s):d=!1;else switch(e.charAt(s)){case"d":c+=l("d",i.getDay(t),2);break;case"D":c+=r("D",t.getDay(),i.dayNamesShort,i.dayNames);break;case"o":c+=l("o",(t.getTime()-new Date(t.getFullYear(),0,0).getTime())/864e5,3);break;case"m":c+=l("m",i.getMonth(t)+1,2);break;case"M":c+=r("M",i.getMonth(t),i.monthNamesShort,i.monthNames);break;case"y":n=i.getYear(t),c+=o("y")?n:(n%100<10?"0":"")+n%100;break;case"h":var u=t.getHours();c+=l("h",u>12?u-12:0===u?12:u,2);break;case"H":c+=l("H",t.getHours(),2);break;case"i":c+=l("i",t.getMinutes(),2);break;case"s":c+=l("s",t.getSeconds(),2);break;case"a":c+=t.getHours()>11?i.pmText:i.amText;break;case"A":c+=t.getHours()>11?i.pmText.toUpperCase():i.amText.toUpperCase();break;case"'":o("'")?c+="'":d=!0;break;default:c+=e.charAt(s)}return c}function V(e,t,a){var s=de({},je,a),n=H(s.defaultValue||new Date);if(!e||!t)return n;if(t.getTime)return t;t="object"==("undefined"==typeof t?"undefined":q(t))?t.toString():t+"";var i,o=s.shortYearCutoff,l=s.getYear(n),r=s.getMonth(n)+1,c=s.getDay(n),d=-1,u=n.getHours(),m=n.getMinutes(),f=0,h=-1,b=!1,p=function(t){var a=i+1<e.length&&e.charAt(i+1)==t;return a&&i++,a},v=function(e){p(e);var a="@"==e?14:"!"==e?20:"y"==e?4:"o"==e?3:2,s=new RegExp("^\\d{1,"+a+"}"),n=t.substr(_).match(s);return n?(_+=n[0].length,parseInt(n[0],10)):0},g=function(e,a,s){var n,i=p(e)?s:a;for(n=0;n<i.length;n++)if(t.substr(_,i[n].length).toLowerCase()==i[n].toLowerCase())return _+=i[n].length,n+1;return 0},y=function(){_++},_=0;for(i=0;i<e.length;i++)if(b)"'"!=e.charAt(i)||p("'")?y():b=!1;else switch(e.charAt(i)){case"d":c=v("d");break;case"D":g("D",s.dayNamesShort,s.dayNames);break;case"o":d=v("o");break;case"m":r=v("m");break;case"M":r=g("M",s.monthNamesShort,s.monthNames);break;case"y":l=v("y");break;case"H":u=v("H");break;case"h":u=v("h");break;case"i":m=v("i");break;case"s":f=v("s");break;case"a":h=g("a",[s.amText,s.pmText],[s.amText,s.pmText])-1;break;case"A":h=g("A",[s.amText,s.pmText],[s.amText,s.pmText])-1;break;case"'":p("'")?y():b=!0;break;default:y()}if(l<100&&(l+=(new Date).getFullYear()-(new Date).getFullYear()%100+(l<=("string"!=typeof o?o:(new Date).getFullYear()%100+parseInt(o,10))?0:-100)),d>-1){r=1,c=d;do{var x=32-new Date(l,r-1,32,12).getDate();c>x&&(r++,c-=x)}while(c>x)}u=h==-1?u:h&&u<12?u+12:h||12!=u?u:0;var w=s.getDate(l,r-1,c,u,m,f);return s.getYear(w)!=l||s.getMonth(w)+1!=r||s.getDay(w)!=c?n:w}function Y(e,t){return Math.round((t-e)/864e5)}function A(e){return k(e.getFullYear(),e.getMonth(),e.getDate())}function E(e){return e.getFullYear()+"-"+(e.getMonth()+1)+"-"+e.getDate()}function P(e,t){var a="",s="";return e&&(t.h&&(s+=c(e.getHours())+":"+c(e.getMinutes()),t.s&&(s+=":"+c(e.getSeconds())),t.u&&(s+="."+c(e.getMilliseconds(),3)),t.tz&&(s+=t.tz)),t.y?(a+=e.getFullYear(),t.m&&(a+="-"+c(e.getMonth()+1),t.d&&(a+="-"+c(e.getDate())),t.h&&(a+="T"+s))):t.h&&(a=s)),a}function $(e,t,a){var s,n,i={y:1,m:2,d:3,h:4,i:5,s:6,u:7,tz:8};if(a)for(s in i)n=e[i[s]-t],n&&(a[s]="tz"==s?n:1)}function W(e,t,a){var s=window.moment||t.moment,n=t.returnFormat;if(e){if("moment"==n&&s)return s(e);if("locale"==n)return S(a,e,t);if("iso8601"==n)return P(e,t.isoParts)}return e}function H(e,t,a,s){var n;return e?e.getTime?e:e.toDate?e.toDate():("string"==typeof e&&(e=e.trim()),(n=Xe.exec(e))?($(n,2,s),new Date(1970,0,1,n[2]?+n[2]:0,n[3]?+n[3]:0,n[4]?+n[4]:0,n[5]?+n[5]:0)):(n||(n=Re.exec(e)),n?($(n,0,s),new Date(n[1]?+n[1]:1970,n[2]?n[2]-1:0,n[3]?+n[3]:1,n[4]?+n[4]:0,n[5]?+n[5]:0,n[6]?+n[6]:0,n[7]?+n[7]:0)):V(t,e,a))):null}function F(e,t){return e.getFullYear()==t.getFullYear()&&e.getMonth()==t.getMonth()&&e.getDate()==t.getDate()}function L(e){return e[0].innerWidth||e.innerWidth()}e=e&&e.hasOwnProperty("default")?e.default:e;var O=O||{},I={},N={},q="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},R=e.extend,X={};O.$=e,e.mobiscroll=O,e.fn.mobiscroll=function(e){return R(this,X),t(this,e,arguments)};var z,U,j,B,G=[],J="undefined"!=typeof window,Z=J?navigator.userAgent:"",K=/Safari/.test(Z),Q=Z.match(/Android|iPhone|iPad|iPod|Windows Phone|Windows|MSIE/i),ee=J&&window.requestAnimationFrame||function(e){return setTimeout(e,20)},te=J&&window.cancelAnimationFrame||function(e){clearTimeout(e)};/Android/i.test(Q)?(z="android",U=Z.match(/Android\s+([\d\.]+)/i),U&&(G=U[0].replace("Android ","").split("."))):/iPhone|iPad|iPod/i.test(Q)?(z="ios",U=Z.match(/OS\s+([\d\_]+)/i),U&&(G=U[0].replace(/_/g,".").replace("OS ","").split("."))):/Windows Phone/i.test(Q)?z="wp":/Windows|MSIE/i.test(Q)&&(z="windows"),j=G[0],B=G[1];var ae=0,se=void 0;J&&(["mouseover","mousedown","mouseup","click"].forEach(function(e){document.addEventListener(e,b,!0)}),"android"==z&&j<5&&document.addEventListener("change",function(e){ae&&"checkbox"==e.target.type&&!e.target.mbscChange&&(e.stopPropagation(),e.preventDefault()),delete e.target.mbscChange},!0)),O.uid="c5d09426";var ne,ie=O.$,oe=+new Date,le={},re={},ce={xsmall:0,small:576,medium:768,large:992,xlarge:1200},de=ie.extend;de(I,{getCoord:f,preventClick:u,vibrate:d}),ne=de(O,{$:ie,version:"4.8.2",autoTheme:"mobiscroll",themes:{form:{},page:{},frame:{},scroller:{},listview:{},navigation:{},progress:{},card:{}},platform:{name:z,majorVersion:j,minorVersion:B},i18n:{},instances:le,classes:re,util:I,settings:{},setDefaults:function(e){de(this.settings,e)},customTheme:function(e,t){var a,s=O.themes,n=["frame","scroller","listview","navigation","form","page","progress","card"];for(a=0;a<n.length;a++)s[n[a]][e]=de({},s[n[a]][t],{baseTheme:t})}});var ue=function(e,t){function a(e){var t,a;return c.responsive&&(a=e||i.offsetWidth,ie.each(c.responsive,function(e,s){a>=(s.breakpoint||ce[e])&&(t=s)})),t}function n(){ie(e).addClass("mbsc-comp"),e.id?le[e.id]&&le[e.id].destroy():e.id="mobiscroll"+ ++oe,le[e.id]=b,b.__ready=!0}var i,o,l,r,c,d,u,m,f,b=this;b.settings={},b.element=e,b._init=s,b._destroy=s,b._processSettings=s,b._checkResp=function(e){if(b&&b._responsive){var t=a(e);if(r!==t)return r=t,b.init({}),!0}},b.init=function(s,n){var h,p;s&&b.getVal&&(p=b.getVal());for(h in b.settings)delete b.settings[h];c=b.settings,de(t,s),b._hasDef&&(f=ne.settings),de(c,b._defaults,f,t),b._hasTheme&&(u=c.theme,"auto"!=u&&u||(u=ne.autoTheme),"default"==u&&(u="mobiscroll"),t.theme=u,d=ne.themes[b._class]?ne.themes[b._class][u]:{}),b._hasLang&&(o=ne.i18n[c.lang]),de(c,d,o,f,t),i=ie(c.context)[0],b._responsive&&(r||(r=a()),de(c,r)),b._processSettings(r||{}),b._presets&&(l=b._presets[c.preset],l&&(l=l.call(e,b,t),de(c,l,t,r))),b._init(s),s&&b.setVal&&b.setVal(void 0===n?p:n,!0),m("onInit")},b.destroy=function(){b&&(b._destroy(),m("onDestroy"),delete le[e.id],b=null)},b.tap=function(e,t,a,s,n){h(b,e,t,a,s,n)},b.trigger=function(a,s){var n,i,o,r=[f,d,l,t];for(i=0;i<4;i++)o=r[i],o&&o[a]&&(n=o[a].call(e,s||{},b));return n},b.option=function(e,a,s){var n={},i=["data","invalid","valid","readonly"];/calendar|eventcalendar|range/.test(c.preset)&&i.push("marked","labels","colors"),"object"===("undefined"==typeof e?"undefined":q(e))?n=e:n[e]=a,i.forEach(function(e){t[e]=c[e]}),b.init(n,s)},b.getInst=function(){return b},t=t||{},m=b.trigger,b.__ready||n()},me=0;I.getJson=g;var fe,he,be,pe,ve,ge,ye={};J&&(he=document.createElement("modernizr").style,be=_(),ge=be.replace(/^\-/,"").replace(/\-$/,"").replace("moz","Moz"),fe=void 0!==he.animation?"animationend":"webkitAnimationEnd",ve=void 0!==he.transition,pe=void 0===he.touchAction||"ios"==z&&!K&&(j<12||12==j&&B<2));var _e,xe,we="position:absolute;left:0;top:0;",Ce=we+"right:0;bottom:0;overflow:hidden;z-index:-1;",Te='<div style="'+Ce+'"><div style="'+we+'"></div></div><div style="'+Ce+'"><div style="'+we+'width:200%;height:200%;"></div></div>',De=O.themes,Me=/(iphone|ipod)/i.test(Z)&&j>=7,ke="android"==z,Se="ios"==z,Ve=Se&&8==j,Ye=Se&&j>7,Ae=function(e){e.preventDefault()},Ee="input,select,textarea,button",Pe='textarea,button,input[type="button"],input[type="submit"]',$e=Ee+',[tabindex="0"]',We=function(e,t,a){function n(e){A&&A.removeClass("mbsc-active"),A=ie(this),A.hasClass("mbsc-disabled")||A.hasClass("mbsc-fr-btn-nhl")||A.addClass("mbsc-active"),"mousedown"===e.type?ie(document).on("mouseup",i):"pointerdown"===e.type&&ie(document).on("pointerup",i)}function i(e){A&&(A.removeClass("mbsc-active"),A=null),"mouseup"===e.type?ie(document).off("mouseup",i):"pointerup"===e.type&&ie(document).off("pointerup",i)}function o(e){O.activeInstance==te&&(13!=e.keyCode||ie(e.target).is(Pe)&&!e.shiftKey?27==e.keyCode&&te.cancel():te.select())}function c(e){e||ke||!te._activeElm||(ne=new Date,te._activeElm.focus())}function d(e){var t=_e,a=G.focusOnClose;te._markupRemove(),C.remove(),W&&(E.mbscModals--,G.scrollLock&&E.mbscLock--,E.mbscLock||w.removeClass("mbsc-fr-lock"),R&&(E.mbscIOSLock--,E.mbscIOSLock||(w.removeClass("mbsc-fr-lock-ios"),_.css({top:"",left:""}),S.scrollLeft(E.mbscScrollLeft),S.scrollTop(E.mbscScrollTop))),E.mbscModals||w.removeClass("mbsc-fr-lock-ctx"),E.mbscModals&&!B||e||(t||(t=ae),setTimeout(function(){void 0===a||a===!0?(xe=!0,t[0].focus()):a&&ie(a)[0].focus()},200))),B=void 0,H=!1,K("onHide")}function m(){clearTimeout(j),j=setTimeout(function(){te.position(!0)&&(U.style.visibility="hidden",U.offsetHeight,U.style.visibility="")},200)}function h(e){O.activeInstance==te&&e.target.nodeType&&!z.contains(e.target)&&new Date-ne>100&&(ne=new Date,te._activeElm.focus())}function b(e,t){function a(){C.off(fe,a).removeClass("mbsc-anim-in mbsc-anim-trans mbsc-anim-trans-"+P).find(".mbsc-fr-popup").removeClass("mbsc-anim-"+P),c(t)}if(W)C.appendTo(_);else if(ae.is("div")&&!te._hasContent)ae.empty().append(C);else if(ae.hasClass("mbsc-control")){var s=ae.closest(".mbsc-control-w");C.insertAfter(s),s.hasClass("mbsc-select")&&s.addClass("mbsc-select-inline")}else C.insertAfter(ae);H=!0,te._markupInserted(C),K("onMarkupInserted",{target:L}),C.on("mousedown",".mbsc-btn-e,.mbsc-fr-btn-e",Ae).on("touchstart mousedown",function(e){G.stopProp&&e.stopPropagation()}).on("keydown",".mbsc-fr-btn-e",function(e){32==e.keyCode&&(e.preventDefault(),e.stopPropagation(),this.click())}).on("keydown",function(e){if(32!=e.keyCode||ie(e.target).is(Ee)){if(9==e.keyCode&&W&&G.focusTrap){var t=C.find($e).filter(function(){return this.offsetWidth>0||this.offsetHeight>0}),a=t.index(ie(":focus",C)),s=t.length-1,n=0;e.shiftKey&&(s=0,n=-1),a===s&&(t.eq(n)[0].focus(),e.preventDefault())}}else e.preventDefault()}).on("touchstart mousedown pointerdown",".mbsc-fr-btn-e",n).on("touchend",".mbsc-fr-btn-e",i),L.addEventListener("touchstart",function(){Z||(Z=!0,_.find(".mbsc-no-touch").removeClass("mbsc-no-touch"))},!0),ie.each(Y,function(e,t){te.tap(ie(".mbsc-fr-btn"+e,C),function(e){t=l(t)?te.buttons[t]:t,(l(t.handler)?te.handlers[t.handler]:t.handler).call(this,e,te)},!0)}),te._attachEvents(C),te.position()!==!1&&((W||te._checkSize)&&(X=D(L,m,G.zone)),W&&(C.removeClass("mbsc-fr-pos"),P&&!e?C.addClass("mbsc-anim-in mbsc-anim-trans mbsc-anim-trans-"+P).on(fe,a).find(".mbsc-fr-popup").addClass("mbsc-anim-"+P):c(t)),K("onShow",{target:L,valueText:te._tempValue}))}function p(e,t){te._isVisible||(e&&e(),te.show()!==!1&&(_e=t))}function v(){te._fillValue(),K("onSet",{valueText:te._value})}function g(){K("onCancel",{valueText:te._value})}function y(){te.setVal(null,!0)}var _,x,w,C,T,M,k,S,V,Y,A,E,P,$,W,H,F,L,I,N,q,R,X,z,U,j,B,G,J,Z,K,Q,ee,te=this,ae=ie(e),se=[],ne=new Date;ue.call(this,e,t,!0),te.position=function(e){var t,a,s,n,i,o,l,c,d,u,m,f,h,b,p,v,g,y,x,w={},T=0,D=0,Y=0,A=0;if(!H)return!1;if(v=Q,p=ee,h=L.offsetHeight,b=L.offsetWidth,b&&h&&(Q!==b||ee!==h||!e)){if(te._checkResp(b))return!1;if(Q=b,ee=h,te._isFullScreen||/top|bottom/.test(G.display)?k.width(b):W&&V.width(""),te._position(C),!te._isFullScreen&&/center|bubble/.test(G.display)&&(ie(".mbsc-w-p",C).each(function(){g=this.getBoundingClientRect().width,A+=g,Y=g>Y?g:Y}),f=A>b-16||G.tabs===!0,V.css({width:te._isLiquid?Math.min(G.maxPopupWidth,b-16):Math.ceil(f?Y:A),"white-space":f?"":"nowrap"})),K("onPosition",{target:L,popup:U,hasTabs:f,oldWidth:v,oldHeight:p,windowWidth:b,windowHeight:h})!==!1&&W)return q&&(T=S.scrollLeft(),D=S.scrollTop(),Q&&M.css({width:"",height:""})),I=U.offsetWidth,N=U.offsetHeight,J=N<=h&&I<=b,"center"==G.display?(x=Math.max(0,T+(b-I)/2),y=Math.max(0,D+(h-N)/2)):"bubble"==G.display?(t=void 0===G.anchor?ae:ie(G.anchor),l=ie(".mbsc-fr-arr-i",C)[0],n=t.offset(),i=n.top+($?D-_.offset().top:0),o=n.left+($?T-_.offset().left:0),a=t[0].offsetWidth,s=t[0].offsetHeight,c=l.offsetWidth,d=l.offsetHeight,x=r(o-(I-a)/2,T+3,T+b-I-3),y=i+s+d/2,y+N+8>D+h&&i-N-d/2>D?(k.removeClass("mbsc-fr-bubble-bottom").addClass("mbsc-fr-bubble-top"),y=i-N-d/2):k.removeClass("mbsc-fr-bubble-top").addClass("mbsc-fr-bubble-bottom"),ie(".mbsc-fr-arr",C).css({left:r(o+a/2-(x+(I-c)/2),0,c)}),J=y>D&&x>T&&y+N<=D+h&&x+I<=T+b):(x=T,y="top"==G.display?D:Math.max(0,D+h-N)),q&&(u=Math.max(y+N,$?E.scrollHeight:ie(document).height()),m=Math.max(x+I,$?E.scrollWidth:ie(document).width()),M.css({width:m,height:u}),G.scroll&&"bubble"==G.display&&(y+N+8>D+h||i>D+h||i+s<D)&&S.scrollTop(Math.min(i,y+N-h+8,u-h))),w.top=Math.floor(y),w.left=Math.floor(x),k.css(w),!0}},te.attachShow=function(e,t){var a,s=ie(e).off(".mbsc"),n=s.prop("readonly");"inline"!==G.display&&((G.showOnFocus||G.showOnTap)&&s.is("input,select")&&(s.prop("readonly",!0).on("mousedown.mbsc",function(e){e.preventDefault()}).on("focus.mbsc",function(){te._isVisible&&this.blur()}),a=ie('label[for="'+s.attr("id")+'"]'),a.length||(a=s.closest("label"))),s.is("select")||(G.showOnFocus&&s.on("focus.mbsc",function(){xe?xe=!1:p(t,s)}),G.showOnTap&&(s.on("keydown.mbsc",function(e){32!=e.keyCode&&13!=e.keyCode||(e.preventDefault(),e.stopPropagation(),p(t,s))}),te.tap(s,function(e){e.isMbscTap&&(Z=!0),p(t,s)}),a&&a.length&&te.tap(a,function(e){e.preventDefault(),e.target!==s[0]&&p(t,s)}))),se.push({readOnly:n,el:s,lbl:a}))},te.select=function(){W?te.hide(!1,"set",!1,v):v()},te.cancel=function(){W?te.hide(!1,"cancel",!1,g):g()},te.clear=function(){te._clearValue(),K("onClear"),W&&te._isVisible&&!te.live?te.hide(!1,"clear",!1,y):y()},te.enable=function(){G.disabled=!1,ie.each(se,function(e,t){t.el.is("input,select")&&(t.el[0].disabled=!1)})},te.disable=function(){G.disabled=!0,ie.each(se,function(e,t){t.el.is("input,select")&&(t.el[0].disabled=!0)})},te.show=function(e,t){var a,s,n,i;if(!G.disabled&&!te._isVisible){if(te._readValue(),K("onBeforeShow")===!1)return!1;if(_e=null,P=G.animate,Y=G.buttons||[],q=$||"bubble"==G.display,R=Me&&!q&&G.scrollLock,a=Y.length>0,P!==!1&&("top"==G.display?P=P||"slidedown":"bottom"==G.display?P=P||"slideup":"center"!=G.display&&"bubble"!=G.display||(P=P||"pop")),W&&(Q=0,ee=0,R&&!w.hasClass("mbsc-fr-lock-ios")&&(E.mbscScrollTop=i=Math.max(0,S.scrollTop()),E.mbscScrollLeft=n=Math.max(0,S.scrollLeft()),_.css({top:-i+"px",left:-n+"px"})),w.addClass((G.scrollLock?"mbsc-fr-lock":"")+(R?" mbsc-fr-lock-ios":"")+($?" mbsc-fr-lock-ctx":"")),ie(document.activeElement).is("input,textarea")&&document.activeElement.blur(),B=O.activeInstance,O.activeInstance=te,E.mbscModals=(E.mbscModals||0)+1,R&&(E.mbscIOSLock=(E.mbscIOSLock||0)+1),G.scrollLock&&(E.mbscLock=(E.mbscLock||0)+1)),s='<div lang="'+G.lang+'" class="mbsc-fr mbsc-'+G.theme+(G.baseTheme?" mbsc-"+G.baseTheme:"")+" mbsc-fr-"+G.display+" "+(G.cssClass||"")+" "+(G.compClass||"")+(te._isLiquid?" mbsc-fr-liq":"")+(W?" mbsc-fr-pos"+(G.showOverlay?"":" mbsc-fr-no-overlay"):"")+(F?" mbsc-fr-pointer":"")+(Ye?" mbsc-fr-hb":"")+(Z?"":" mbsc-no-touch")+(R?" mbsc-platform-ios":"")+(a?Y.length>=3?" mbsc-fr-btn-block ":"":" mbsc-fr-nobtn")+'">'+(W?'<div class="mbsc-fr-persp">'+(G.showOverlay?'<div class="mbsc-fr-overlay"></div>':"")+'<div role="dialog" class="mbsc-fr-scroll">':"")+'<div class="mbsc-fr-popup'+(G.rtl?" mbsc-rtl":" mbsc-ltr")+(G.headerText?" mbsc-fr-has-hdr":"")+'">'+("bubble"===G.display?'<div class="mbsc-fr-arr-w"><div class="mbsc-fr-arr-i"><div class="mbsc-fr-arr"></div></div></div>':"")+(W?'<div class="mbsc-fr-focus" tabindex="-1"></div>':"")+'<div class="mbsc-fr-w">'+(G.headerText?'<div class="mbsc-fr-hdr">'+(l(G.headerText)?G.headerText:"")+"</div>":"")+'<div class="mbsc-fr-c">',s+=te._generateContent(),s+="</div>",a){var r,c,d,m=Y.length;for(s+='<div class="mbsc-fr-btn-cont">',c=0;c<Y.length;c++)d=G.btnReverse?m-c-1:c,r=Y[d],r=l(r)?te.buttons[r]:r,"set"===r.handler&&(r.parentClass="mbsc-fr-btn-s"),"cancel"===r.handler&&(r.parentClass="mbsc-fr-btn-c"),s+="<div"+(G.btnWidth?' style="width:'+100/Y.length+'%"':"")+' class="mbsc-fr-btn-w '+(r.parentClass||"")+'"><div tabindex="0" role="button" class="mbsc-fr-btn'+d+" mbsc-fr-btn-e "+(void 0===r.cssClass?G.btnClass:r.cssClass)+(r.icon?" mbsc-ic mbsc-ic-"+r.icon:"")+'">'+(r.text||"")+"</div></div>";s+="</div>"}if(s+="</div></div></div></div>"+(W?"</div></div>":""),C=ie(s),M=ie(".mbsc-fr-persp",C),T=ie(".mbsc-fr-scroll",C),V=ie(".mbsc-fr-w",C),k=ie(".mbsc-fr-popup",C),x=ie(".mbsc-fr-hdr",C),L=C[0],z=T[0],U=k[0],te._activeElm=ie(".mbsc-fr-focus",C)[0],te._markup=C,te._isVisible=!0,te.markup=L,te._markupReady(C),K("onMarkupReady",{target:L}),W&&(ie(window).on("keydown",o),G.scrollLock&&C.on("touchmove mousewheel wheel",function(e){J&&e.preventDefault()}),G.focusTrap&&S.on("focusin",h),G.closeOnOverlayTap)){var p,v,g,y;T.on("touchstart mousedown",function(e){v||e.target!=z||(v=!0,p=!1,g=f(e,"X"),y=f(e,"Y"))}).on("touchmove mousemove",function(e){v&&!p&&(Math.abs(f(e,"X")-g)>9||Math.abs(f(e,"Y")-y)>9)&&(p=!0)}).on("touchcancel",function(){v=!1}).on("touchend click",function(e){v&&!p&&(te.cancel(),"touchend"==e.type&&u()),v=!1})}W&&R?setTimeout(function(){b(e,t)},100):b(e,t)}},te.hide=function(e,t,a,s){function n(){C.off(fe,n),d(e)}return!(!te._isVisible||!a&&!te._isValid&&"set"==t||!a&&K("onBeforeClose",{valueText:te._tempValue,button:t})===!1)&&(te._isVisible=!1,X&&(X.detach(),X=null),W&&(ie(document.activeElement).is("input,textarea")&&U.contains(document.activeElement)&&document.activeElement.blur(),O.activeInstance==te&&(O.activeInstance=B),ie(window).off("keydown",o),S.off("focusin",h)),C&&(W&&P&&!e?C.addClass("mbsc-anim-out mbsc-anim-trans mbsc-anim-trans-"+P).on(fe,n).find(".mbsc-fr-popup").addClass("mbsc-anim-"+P):d(e),te._detachEvents(C)),s&&s(),ae.trigger("blur"),void K("onClose",{valueText:te._value}))},te.isVisible=function(){return te._isVisible},te.setVal=s,te.getVal=s,te._generateContent=s,te._attachEvents=s,te._detachEvents=s,te._readValue=s,te._clearValue=s,te._fillValue=s,te._markupReady=s,te._markupInserted=s,te._markupRemove=s,te._position=s,te.__processSettings=s,te.__init=s,te.__destroy=s,te._destroy=function(){te.hide(!0,!1,!0),ae.off(".mbsc"),ie.each(se,function(e,t){t.el.off(".mbsc").prop("readonly",t.readOnly),t.lbl&&t.lbl.off(".mbsc")}),te.__destroy()},te._updateHeader=function(){var t=G.headerText,a=t?"function"==typeof t?t.call(e,te._tempValue):t.replace(/\{value\}/i,te._tempValue):"";x.html(a||"&nbsp;")},te._processSettings=function(e){var a,s;for(te.__processSettings(e),F=!G.touchUi,F&&(G.display=e.display||t.display||"bubble",G.buttons=e.buttons||t.buttons||[],G.showOverlay=e.showOverlay||t.showOverlay||!1),G.buttons=G.buttons||("inline"!==G.display?["cancel","set"]:[]),G.headerText=void 0===G.headerText?"inline"!==G.display&&"{value}":G.headerText,Y=G.buttons||[],W="inline"!==G.display,$="body"!=G.context,_=ie(G.context),w=$?_:ie("body,html"),E=_[0],te._$window=S=ie($?G.context:window),te.live=!0,s=0;s<Y.length;s++)a=Y[s],"ok"!=a&&"set"!=a&&"set"!=a.handler||(te.live=!1);te.buttons.set={text:G.setText,icon:G.setIcon,handler:"set"},te.buttons.cancel={text:G.cancelText,icon:G.cancelIcon,handler:"cancel"},te.buttons.close={text:G.closeText,icon:G.closeIcon,handler:"cancel"},te.buttons.clear={text:G.clearText,icon:G.clearIcon,handler:"clear"},te._isInput=ae.is("input")},te._init=function(e){var t=te._isVisible,a=t&&!C.hasClass("mbsc-fr-pos");t&&te.hide(!0,!1,!0),ae.off(".mbsc"),te.__init(e),te._isLiquid="liquid"==G.layout,W?(te._readValue(),te._hasContent||G.skipShow||te.attachShow(ae),t&&te.show(a)):te.show(),ae.removeClass("mbsc-cloak").filter("input, select, textarea").on("change.mbsc",function(){te._preventChange||te.setVal(ae.val(),!0,!1),te._preventChange=!1})},te.buttons={},te.handlers={set:te.select,cancel:te.cancel,clear:te.clear},te._value=null,te._isValid=!0,te._isVisible=!1,G=te.settings,K=te.trigger,a||te.init()};We.prototype._defaults={lang:"en",setText:"Set",selectedText:"{count} selected",closeText:"Close",cancelText:"Cancel",clearText:"Clear",context:"body",maxPopupWidth:600,disabled:!1,closeOnOverlayTap:!0,showOnFocus:ke||Se,showOnTap:!0,display:"center",scroll:!0,scrollLock:!0,showOverlay:!0,tap:!0,touchUi:!0,btnClass:"mbsc-fr-btn",btnWidth:!0,focusTrap:!0,focusOnClose:!Ve},re.Frame=We,De.frame.mobiscroll={headerText:!1,btnWidth:!1},De.scroller.mobiscroll=de({},De.frame.mobiscroll,{rows:5,showLabel:!1,selectedLineBorder:1,weekDays:"min",checkIcon:"ion-ios7-checkmark-empty",btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5"}),J&&ie(window).on("focus",function(){_e&&(xe=!0)});/* eslint-disable no-unused-vars */
var He="ios"==z,Fe=function(e,t,a){function s(e){fe("onStart",{domEvent:e}),xe.stopProp&&e.stopPropagation(),xe.prevDef&&e.preventDefault(),xe.readonly||xe.lock&&N||x(e,this)&&!I&&(y&&y.removeClass("mbsc-active"),W=!1,N||(y=ie(e.target).closest(".mbsc-btn-e",this),y.length&&!y.hasClass("mbsc-disabled")&&(W=!0,T=setTimeout(function(){y.addClass("mbsc-active")},100))),I=!0,G=!1,q=!1,ve.scrolled=N,oe=f(e,"X"),le=f(e,"Y"),E=oe,M=0,k=0,S=0,ne=new Date,se=+w(ce,he)||0,N&&g(se,He?0:1),"mousedown"===e.type&&ie(document).on("mousemove",n).on("mouseup",c))}function n(e){I&&(xe.stopProp&&e.stopPropagation(),E=f(e,"X"),P=f(e,"Y"),M=E-oe,k=P-le,S=he?k:M,W&&(Math.abs(k)>xe.thresholdY||Math.abs(M)>xe.thresholdX)&&(clearTimeout(T),y.removeClass("mbsc-active"),W=!1),(ve.scrolled||!q&&Math.abs(S)>me)&&(G||fe("onGestureStart",$),ve.scrolled=G=!0,X||(X=!0,R=ee(i))),he||xe.scrollLock?e.preventDefault():ve.scrolled?e.preventDefault():Math.abs(k)>7&&(q=!0,ve.scrolled=!0,c()))}function i(){L&&(S=r(S,-Q*L,Q*L)),g(r(se+S,O-A,F+A)),X=!1}function c(e){if(I){var t,a=new Date-ne;xe.stopProp&&e&&e.stopPropagation(),te(R),X=!1,!q&&ve.scrolled&&(xe.momentum&&a<300&&(t=S/a,S=Math.max(Math.abs(S),t*t/xe.speedUnit)*(S<0?-1:1)),v(S)),W&&(clearTimeout(T),y.addClass("mbsc-active"),setTimeout(function(){y.removeClass("mbsc-active")},100),q||ve.scrolled||fe("onBtnTap",{target:y[0],domEvent:e})),e&&"mouseup"==e.type&&ie(document).off("mousemove",n).off("mouseup",c),I=!1}}function d(e){if(e=e.originalEvent||e,S=he?void 0==e.deltaY?e.wheelDelta||e.detail:e.deltaY:e.deltaX,fe("onStart",{domEvent:e}),xe.stopProp&&e.stopPropagation(),S){if(e.preventDefault(),e.deltaMode&&1==e.deltaMode&&(S*=15),S=r(-S,-Z,Z),se=pe,xe.readonly)return;if(G||p(),se+S<O&&(se=O,S=0),se+S>F&&(se=F,S=0),X||(X=!0,R=ee(i)),!S&&G)return;G=!0,clearTimeout(J),J=setTimeout(function(){te(R),X=!1,G=!1,v(S)},200)}}function u(e){fe("onStart",{domEvent:e}),xe.readonly||(e.stopPropagation(),se=pe,G=!1,e.target==z?(le=f(e,"Y",!0),ie(document).on("mousemove",m).on("mouseup",h)):(le=_.offset().top,m(e),h()))}function m(e){var t=(f(e,"Y",!0)-le)/D;H?(S=-(L*Q*2+D)*t,S=r(S,-Q*L,Q*L)):S=(O-F-D)*t,G||p(),G=!0,g(r(se+S,O-A,F+A))}function h(){se=pe,v(0),ie(document).off("mousemove",m).off("mouseup",h)}function b(e){e.stopPropagation()}function p(){$={posX:he?0:pe,posY:he?pe:0,originX:he?0:se,originY:he?se:0,direction:S>0?he?270:360:he?90:180},fe("onGestureStart",$)}function v(e){var t,a,s;if(L&&(e=r(e,-Q*L,Q*L)),s=r(Math.round((se+e)/Q)*Q,O,F),ae){if(e<0){for(t=ae.length-1;t>=0;t--)if(Math.abs(s)+D>=ae[t].breakpoint){ye=t,_e=2,s=ae[t].snap2;break}}else if(e>=0)for(t=0;t<ae.length;t++)if(Math.abs(s)<=ae[t].breakpoint){ye=t,_e=1,s=ae[t].snap1;break}s=r(s,O,F)}a=xe.time||(pe<O||pe>F?1e3:Math.max(1e3,Math.abs(s-pe)*xe.timeUnit)),$.destinationX=he?0:s,$.destinationY=he?s:0,$.duration=a,$.transitionTiming=Y,fe("onGestureEnd",$),ve.scroll(s,a)}function g(e,t,a,s){var n,i=e!=pe,o=t>1,l=t?be+"transform "+Math.round(t)+"ms "+Y:"",r=function(){clearInterval(K),clearTimeout(de),N=!1,pe=e,$.posX=he?0:e,$.posY=he?e:0,i&&fe("onMove",$),o&&fe("onAnimationEnd",$),s&&s()};$={posX:he?0:pe,posY:he?pe:0,originX:he?0:se,originY:he?se:0,direction:e-pe>0?he?270:360:he?90:180},pe=e,o&&($.destinationX=he?0:e,$.destinationY=he?e:0,$.duration=t,$.transitionTiming=Y,fe("onAnimationStart",$)),re[ge+"Transition"]=l,re[ge+"Transform"]="translate3d("+(he?"0,"+e+"px,":e+"px,0,")+"0)",z&&U&&(n=H?(j-e)/(L*Q*2):(e-F)/(O-F),z.style[ge+"Transition"]=l,z.style[ge+"Transform"]="translate3d(0,"+Math.max(0,Math.min((D-U)*n,D-U))+"px,0)"),!i&&!N||!t||t<=1?r():t&&(N=!a,clearInterval(K),K=setInterval(function(){var t=+w(ce,he)||0;$.posX=he?0:t,$.posY=he?t:0,fe("onMove",$),Math.abs(t-e)<2&&r()},100),clearTimeout(de),de=setTimeout(function(){r()},t)),xe.sync&&xe.sync(e,t,Y)}var y,_,C,T,D,M,k,S,V,Y,A,E,P,$,W,H,F,L,O,I,N,q,R,X,z,U,j,B,G,J,Z,K,Q,ae,se,ne,oe,le,re,ce,de,me,fe,he,pe,ve=this,ye=0,_e=1,xe=t,we=ie(e);ue.call(this,e,t,!0),ve.scrolled=!1,ve.scroll=function(t,a,s,n){t=o(t)?Math.round(t/Q)*Q:Math.ceil((ie(t,e).length?Math.round(ce.offset()[V]-ie(t,e).offset()[V]):pe)/Q)*Q,t=r(t,O,F),ye=Math.round(t/Q),se=pe,j=L*Q+t,g(t,a,s,n)},ve.refresh=function(e){var t;for(D=(void 0===xe.contSize?he?we.height():we.width():xe.contSize)||0,F=(void 0===xe.maxScroll?0:xe.maxScroll)||0,O=Math.min(F,void 0===xe.minScroll?Math.min(0,he?D-ce.height():D-ce.width()):xe.minScroll)||0,ae=null,!he&&xe.rtl&&(t=F,F=-O,O=-t),l(xe.snap)&&(ae=[],ce.find(xe.snap).each(function(){var e=he?this.offsetTop:this.offsetLeft,t=he?this.offsetHeight:this.offsetWidth;ae.push({breakpoint:e+t/2,snap1:-e,snap2:D-e-t})})),Q=o(xe.snap)?xe.snap:1,L=xe.snap?xe.maxSnapScroll:0,Y=xe.easing,A=xe.elastic?o(xe.snap)?Q:o(xe.elastic)?xe.elastic:0:0,Z=Q;Z>44;)Z/=2;Z=Math.round(44/Z)*Z,z&&(H=O==-(1/0)||F==1/0,U=O<F?Math.max(20,D*D/(F-O+D)):0,z.style.height=U+"px",B.style.height=U?"":0),void 0===pe&&(pe=xe.initialPos,ye=Math.round(pe/Q)),e||ve.scroll(xe.snap?ae?ae[ye]["snap"+_e]:ye*Q:pe)},ve._processSettings=function(){he="Y"==xe.axis,V=he?"top":"left",ce=xe.moveElement||we.children().eq(0),re=ce[0].style,me=he?xe.thresholdY:xe.thresholdX,xe.scrollbar&&(C=xe.scrollbar,_=C.find(".mbsc-sc-bar"),z=_[0],B=C[0])},ve._init=function(){ve.refresh(),we.on("touchstart mousedown",s).on("touchmove",n).on("touchend touchcancel",c),xe.mousewheel&&we.on("wheel mousewheel",d),z&&C.on("mousedown",u).on("click",b),e.addEventListener("click",function(e){ve.scrolled&&(ve.scrolled=!1,e.stopPropagation(),e.preventDefault())},!0)},ve._destroy=function(){clearInterval(K),we.off("touchstart mousedown",s).off("touchmove",n).off("touchend touchcancel",c).off("wheel mousewheel",d),z&&C.off("mousedown",u).off("click",b)},xe=ve.settings,fe=ve.trigger,a||ve.init()};Fe.prototype={_defaults:{speedUnit:.0022,timeUnit:3,initialPos:0,axis:"Y",thresholdX:10,thresholdY:5,easing:"cubic-bezier(0.190, 1.000, 0.220, 1.000)",stopProp:!0,momentum:!0,mousewheel:!0,elastic:!0}};/* eslint-disable no-unused-vars */
var Le={},Oe=J?window.CSS:null,Ie=Oe&&Oe.supports&&Oe.supports("(transform-style: preserve-3d)"),Ne=function(e,t,a){function s(e){var t,a,s=+ie(this).attr("data-index");38==e.keyCode?(t=!0,a=-1):40==e.keyCode?(t=!0,a=1):32==e.keyCode&&(t=!0,l(s,ie(e.target))),t&&(e.stopPropagation(),e.preventDefault(),a&&P.start(s,a,e))}function i(){P.stop()}function l(e,t){var a=q[e],s=+t.attr("data-index"),i=b(a,s),l=U._tempSelected[e],r=o(a.multiple)?a.multiple:1/0;I("onItemTap",{target:t[0],index:e,value:i,selected:t.hasClass("mbsc-sc-itm-sel")})!==!1&&(a.multiple&&!a._disabled[i]&&(void 0!==l[i]?(t.removeClass(A).removeAttr("aria-selected"),delete l[i]):(1==r&&(U._tempSelected[e]=l={},a._$markup.find(".mbsc-sc-itm-sel").removeClass(A).removeAttr("aria-selected")),n(l).length<r&&(t.addClass(A).attr("aria-selected","true"),l[i]=i))),C(a,e,s,z,a._index<s?1:2,!0,a.multiple),U.live&&(!a.multiple||1===a.multiple&&O.tapSelect)&&(O.setOnTap===!0||O.setOnTap[e])&&setTimeout(function(){U.select()},O.tapSelect?0:200))}function r(e,t){var a=q[e];return a&&(!a.multiple||1!==a.multiple&&t&&(O.setOnTap===!0||O.setOnTap[e]))}function c(e){return-(e.max-e._offset-(e.multiple&&!Y?Math.floor(O.rows/2):0))*W}function d(e){return-(e.min-e._offset+(e.multiple&&!Y?Math.floor(O.rows/2):0))*W}function u(e,t){return(e._array?e._map[t]:+e.getIndex(t,U))||0}function m(e,t){var a=e.data;if(t>=e.min&&t<=e.max)return e._array?e.circular?ie(a).get(t%e._length):a[t]:ie.isFunction(a)?a(t,U):""}function f(e){return ie.isPlainObject(e)?void 0!==e.value?e.value:e.display:e}function h(e){var t=ie.isPlainObject(e)?e.display:e;return void 0===t?"":t}function b(e,t){return f(m(e,t))}function p(e,t,a){var s=q[e];C(s,e,s._index+t,O.delay+100,1==t?1:2,!1,!1,"keydown"==a.type)}function v(e){return ie.isArray(O.readonly)?O.readonly[e]:O.readonly}function g(e,t,a){var s=e._index-e._batch;return e.data=e.data||[],e.key=void 0!==e.key?e.key:t,e.label=void 0!==e.label?e.label:t,e._map={},e._array=ie.isArray(e.data),e._array&&(e._length=e.data.length,ie.each(e.data,function(t,a){e._map[f(a)]=t})),e.circular=void 0===O.circular?void 0===e.circular?e._array&&e._length>O.rows:e.circular:ie.isArray(O.circular)?O.circular[t]:O.circular,e.min=e._array?e.circular?-(1/0):0:void 0===e.min?-(1/0):e.min,e.max=e._array?e.circular?1/0:e._length-1:void 0===e.max?1/0:e.max,e._nr=t,e._index=u(e,$[t]),e._disabled={},e._batch=0,e._current=e._index,e._first=e._index-X,e._last=e._index+X,e._offset=e._first,a?(e._offset-=e._margin/W+(e._index-s),e._margin+=(e._index-s)*W):e._margin=0,e._refresh=function(t){de(e._scroller.settings,{minScroll:c(e),maxScroll:d(e)}),e._scroller.refresh(t)},R[e.key]=e,e}function y(e,t,a,s,n){var i,o,l,r,c,d,u,b,p="",v=U._tempSelected[t],g=e._disabled||{};for(i=a;i<=s;i++)l=m(e,i),c=h(l),r=f(l),o=l&&void 0!==l.cssClass?l.cssClass:"",d=l&&void 0!==l.label?l.label:"",u=l&&l.invalid,b=void 0!==r&&r==$[t]&&!e.multiple,p+='<div role="option" tabindex="-1" aria-selected="'+!!v[r]+'" class="mbsc-sc-itm '+(n?"mbsc-sc-itm-3d ":"")+o+" "+(b?"mbsc-sc-itm-sel ":"")+(v[r]?A:"")+(void 0===r?" mbsc-sc-itm-ph":" mbsc-btn-e")+(u?" mbsc-sc-itm-inv-h mbsc-disabled":"")+(g[r]?" mbsc-sc-itm-inv mbsc-disabled":"")+'" data-index="'+i+'" data-val="'+M(r)+'"'+(d?' aria-label="'+d+'"':"")+(b?' aria-selected="true"':"")+' style="height:'+W+"px;line-height:"+W+"px;"+(n?be+"transform:rotateX("+(e._offset-i)*V%360+"deg) translateZ("+W*O.rows/2+"px);":"")+'">'+(N>1?'<div class="mbsc-sc-itm-ml" style="line-height:'+Math.round(W/N)+"px;font-size:"+Math.round(W/N*.8)+'px;">':"")+c+(N>1?"</div>":"")+"</div>";return p}function _(e,t,a){var s=Math.round(-a/W)+e._offset,n=s-e._current,i=e._first,o=e._last,l=i+X-S+1,r=o-X+S;n&&(e._first+=n,e._last+=n,e._current=s,n>0?(e._$scroller.append(y(e,t,Math.max(o+1,i+n),o+n)),ie(".mbsc-sc-itm",e._$scroller).slice(0,Math.min(n,o-i+1)).remove(),Y&&(e._$3d.append(y(e,t,Math.max(r+1,l+n),r+n,!0)),ie(".mbsc-sc-itm",e._$3d).slice(0,Math.min(n,r-l+1)).attr("class","mbsc-sc-itm-del"))):n<0&&(e._$scroller.prepend(y(e,t,i+n,Math.min(i-1,o+n))),ie(".mbsc-sc-itm",e._$scroller).slice(Math.max(n,i-o-1)).remove(),Y&&(e._$3d.prepend(y(e,t,l+n,Math.min(l-1,r+n),!0)),ie(".mbsc-sc-itm",e._$3d).slice(Math.max(n,l-r-1)).attr("class","mbsc-sc-itm-del"))),e._margin+=n*W,e._$scroller.css("margin-top",e._margin+"px"))}function x(e,t,a,s){var n,i=q[e],o=s||i._disabled,l=u(i,t),r=b(i,l),c=r,d=r,m=0,f=0;if(o[r]===!0){for(n=0;l-m>=i.min&&o[c]&&n<100;)n++,m++,c=b(i,l-m);for(n=0;l+f<i.max&&o[d]&&n<100;)n++,f++,d=b(i,l+f);r=(f<m&&f&&2!==a||!m||l-m<0||1==a)&&!o[d]?d:c}return r}function w(t,a,s,n,i,o,l){var c,d,m,f,h=U._isVisible;L=!0,f=O.validate.call(e,{values:$.slice(0),index:a,direction:s},U)||{},L=!1,f.valid&&(U._tempWheelArray=$=f.valid.slice(0)),o||ie.each(q,function(e,n){if(h&&n._$markup.find(".mbsc-sc-itm-inv").removeClass("mbsc-sc-itm-inv mbsc-disabled"),n._disabled={},f.disabled&&f.disabled[e]&&ie.each(f.disabled[e],function(e,t){n._disabled[t]=!0,h&&n._$markup.find('.mbsc-sc-itm[data-val="'+M(t)+'"]').addClass("mbsc-sc-itm-inv mbsc-disabled")}),$[e]=n.multiple?$[e]:x(e,$[e],s),h){if(n.multiple&&void 0!==a||n._$markup.find(".mbsc-sc-itm-sel").removeClass(A).removeAttr("aria-selected"),d=u(n,$[e]),c=d-n._index+n._batch,Math.abs(c)>2*X+1&&(m=c+(2*X+1)*(c>0?-1:1),n._offset+=m,n._margin-=m*W,n._refresh()),n._index=d+n._batch,n.multiple){if(void 0===a)for(var o in U._tempSelected[e])n._$markup.find('.mbsc-sc-itm[data-val="'+M(o)+'"]').addClass(A).attr("aria-selected","true")}else n._$markup.find('.mbsc-sc-itm[data-val="'+M($[e])+'"]').addClass("mbsc-sc-itm-sel").attr("aria-selected","true");n._$active&&n._$active.attr("tabindex",-1),n._$active=n._$markup.find('.mbsc-sc-itm[data-index="'+n._index+'"]').eq(Y&&n.multiple?1:0).attr("tabindex",0),l&&a===e&&n._$active.length&&(n._$active[0].focus(),n._$scroller.parent().scrollTop(0)),n._scroller.scroll(-(d-n._offset+n._batch)*W,a===e||void 0===a?t:z,i)}}),I("onValidated",{index:a,time:t}),U._tempValue=O.formatValue.call(e,$,U),h&&U._updateHeader(),U.live&&r(a,o)&&(U._hasValue=n||U._hasValue,D(n,n,0,!0),n&&I("onSet",{valueText:U._value})),n&&I("onChange",{index:a,valueText:U._tempValue})}function C(e,t,a,s,n,i,o,l){var r=b(e,a);void 0!==r&&($[t]=r,e._batch=e._array?Math.floor(a/e._length)*e._length:0,e._index=a,setTimeout(function(){w(s,t,n,!0,i,o,l)},10))}function D(t,a,s,n,i){if(n?U._tempValue=O.formatValue.call(e,U._tempWheelArray,U):w(s),!i){U._wheelArray=[];for(var o=0;o<$.length;o++)U._wheelArray[o]=q[o]&&q[o].multiple?Object.keys(U._tempSelected[o]||{})[0]:$[o];U._value=U._hasValue?U._tempValue:null,U._selected=de(!0,{},U._tempSelected)}t&&(U._isInput&&j.val(U._hasValue?U._tempValue:""),I("onFill",{valueText:U._hasValue?U._tempValue:"",change:a}),a&&(U._preventChange=!0,j.trigger("change")))}var k,S,V,Y,A,E,P,$,W,H,F,L,O,I,N,q,R,X=40,z=1e3,U=this,j=ie(e);We.call(this,e,t,!0),U.setVal=U._setVal=function(t,a,s,n,i){U._hasValue=null!==t&&void 0!==t,U._tempWheelArray=$=ie.isArray(t)?t.slice(0):O.parseValue.call(e,t,U)||[],D(a,void 0===s?a:s,i,!1,n)},U.getVal=U._getVal=function(e){var t=U._hasValue||e?U[e?"_tempValue":"_value"]:null;return o(t)?+t:t},U.setArrayVal=U.setVal,U.getArrayVal=function(e){return e?U._tempWheelArray:U._wheelArray},U.changeWheel=function(e,t,a){var s,n;ie.each(e,function(e,t){n=R[e],n&&(s=n._nr,de(n,t),g(n,s,!0),U._isVisible&&(Y&&n._$3d.html(y(n,s,n._first+X-S+1,n._last-X+S,!0)),n._$scroller.html(y(n,s,n._first,n._last)).css("margin-top",n._margin+"px"),n._refresh(L)))}),!U._isVisible||U._isLiquid||L||U.position(),L||w(t,void 0,void 0,a)},U.getValidValue=x,U._generateContent=function(){var e,t=0,a="",s=Y?be+"transform: translateZ("+(W*O.rows/2+3)+"px);":"",n='<div class="mbsc-sc-whl-l" style="'+s+"height:"+W+"px;margin-top:-"+(W/2+(O.selectedLineBorder||0))+'px;"></div>',i=0;return ie.each(O.wheels,function(o,l){a+='<div class="mbsc-w-p mbsc-sc-whl-gr-c'+(Y?" mbsc-sc-whl-gr-3d-c":"")+(O.showLabel?" mbsc-sc-lbl-v":"")+'">'+n+'<div class="mbsc-sc-whl-gr'+(Y?" mbsc-sc-whl-gr-3d":"")+(E?" mbsc-sc-cp":"")+(O.width||O.maxWidth?'"':'" style="max-width:'+O.maxPopupWidth+'px;"')+">",ie.each(l,function(o,l){U._tempSelected[i]=de({},U._selected[i]),q[i]=g(l,i),t+=O.maxWidth?O.maxWidth[i]||O.maxWidth:O.width?O.width[i]||O.width:0,e=void 0!==l.label?l.label:o,a+='<div class="mbsc-sc-whl-w '+(l.cssClass||"")+(l.multiple?" mbsc-sc-whl-multi":"")+'" style="'+(O.width?"width:"+(O.width[i]||O.width)+"px;":(O.minWidth?"min-width:"+(O.minWidth[i]||O.minWidth)+"px;":"")+(O.maxWidth?"max-width:"+(O.maxWidth[i]||O.maxWidth)+"px;":""))+'">'+(F?'<div class="mbsc-sc-bar-c"><div class="mbsc-sc-bar"></div></div>':"")+'<div class="mbsc-sc-whl-o" style="'+s+'"></div>'+n+'<div aria-live="off" aria-label="'+e+'"'+(l.multiple?' aria-multiselectable="true"':"")+' role="listbox" data-index="'+i+'" class="mbsc-sc-whl" style="height:'+O.rows*W*(Y?1.1:1)+'px;">'+(E?'<div data-index="'+i+'" data-step="1" class="mbsc-sc-btn mbsc-sc-btn-plus '+(O.btnPlusClass||"")+'"></div><div data-index="'+i+'" data-step="-1" class="mbsc-sc-btn mbsc-sc-btn-minus '+(O.btnMinusClass||"")+'"></div>':"")+'<div class="mbsc-sc-lbl">'+e+'</div><div class="mbsc-sc-whl-c" style="height:'+H+"px;margin-top:-"+(H/2+1)+"px;"+s+'"><div class="mbsc-sc-whl-sc" style="top:'+(H-W)/2+'px;">',a+=y(l,i,l._first,l._last)+"</div></div>",Y&&(a+='<div class="mbsc-sc-whl-3d" style="height:'+W+"px;margin-top:-"+W/2+'px;">',a+=y(l,i,l._first+X-S+1,l._last-X+S,!0),a+="</div>"),a+="</div></div>",i++}),a+="</div></div>"}),t&&(O.maxPopupWidth=t),a},U._attachEvents=function(e){P=T(ie(".mbsc-sc-btn",e),p,O.delay,v,!0),ie(".mbsc-sc-whl",e).on("keydown",s).on("keyup",i)},U._detachEvents=function(){P.stop();for(var e=0;e<q.length;e++)q[e]._scroller.destroy()},U._markupReady=function(e){k=e,ie(".mbsc-sc-whl-w",k).each(function(e){var t,a=ie(this),s=q[e];s._$markup=a,s._$scroller=ie(".mbsc-sc-whl-sc",this),s._$3d=ie(".mbsc-sc-whl-3d",this),s._scroller=new Fe(this,{mousewheel:O.mousewheel,moveElement:s._$scroller,scrollbar:ie(".mbsc-sc-bar-c",this),initialPos:(s._first-s._index)*W,contSize:O.rows*W,snap:W,minScroll:c(s),maxScroll:d(s),maxSnapScroll:X,prevDef:!0,stopProp:!0,timeUnit:3,easing:"cubic-bezier(0.190, 1.000, 0.220, 1.000)",sync:function(e,t,a){var n=t?be+"transform "+Math.round(t)+"ms "+a:"";Y&&(s._$3d[0].style[ge+"Transition"]=n,s._$3d[0].style[ge+"Transform"]="rotateX("+-e/W*V+"deg)")},onStart:function(t,a){a.settings.readonly=v(e)},onGestureStart:function(){a.addClass("mbsc-sc-whl-a mbsc-sc-whl-anim"),I("onWheelGestureStart",{index:e})},onGestureEnd:function(a){var n=90==a.direction?1:2,i=a.duration,o=a.destinationY;t=Math.round(-o/W)+s._offset,C(s,e,t,i,n)},onAnimationStart:function(){a.addClass("mbsc-sc-whl-anim")},onAnimationEnd:function(){a.removeClass("mbsc-sc-whl-a mbsc-sc-whl-anim"),I("onWheelAnimationEnd",{index:e}),s._$3d.find(".mbsc-sc-itm-del").remove()},onMove:function(t){_(s,e,t.posY)},onBtnTap:function(t){l(e,ie(t.target))}})}),w()},U._fillValue=function(){U._hasValue=!0,D(!0,!0,0,!0)},U._clearValue=function(){ie(".mbsc-sc-whl-multi .mbsc-sc-itm-sel",k).removeClass(A).removeAttr("aria-selected")},U._readValue=function(){var t=j.val()||"",a=0;""!==t&&(U._hasValue=!0),U._tempWheelArray=$=U._hasValue&&U._wheelArray?U._wheelArray.slice(0):O.parseValue.call(e,t,U)||[],U._tempSelected=de(!0,{},U._selected),ie.each(O.wheels,function(e,t){ie.each(t,function(e,t){q[a]=g(t,a),a++})}),D(!1,!1,0,!0),I("onRead")},U.__processSettings=function(e){O=U.settings,I=U.trigger,N=O.multiline,A="mbsc-sc-itm-sel mbsc-ic mbsc-ic-"+O.checkIcon,F=!O.touchUi,F&&(O.tapSelect=!0,O.circular=!1,O.rows=e.rows||t.rows||7)},U.__init=function(e){e&&(U._wheelArray=null),q=[],R={},E=O.showScrollArrows,Y=O.scroll3d&&Ie&&!E&&!F&&("ios"==O.theme||"ios"==O.baseTheme),W=O.height,H=Y?2*Math.round((W-.03*(W*O.rows/2+3))/2):W,S=Math.round(1.8*O.rows),V=360/(2*S),E&&(O.rows=Math.max(3,O.rows))},U._getItemValue=f,U._tempSelected={},U._selected={},a||U.init()};Ne.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_class:"scroller",_presets:Le,_defaults:de({},We.prototype._defaults,{minWidth:80,height:40,rows:3,multiline:1,delay:200,readonly:!1,showLabel:!0,setOnTap:!1,wheels:[],preset:"",speedUnit:.0012,timeUnit:.08,checkIcon:"checkmark",compClass:"mbsc-sc",validate:function(){},formatValue:function(e){return e.join(" ")},parseValue:function(e,t){var a,s,n=[],i=[],o=0;return null!==e&&void 0!==e&&(n=(e+"").split(" ")),ie.each(t.settings.wheels,function(e,l){ie.each(l,function(e,l){s=l.data,a=t._getItemValue(s[0]),ie.each(s,function(e,s){if(n[o]==t._getItemValue(s))return a=t._getItemValue(s),!1}),i.push(a),o++})}),i}})},re.Scroller=Ne;var qe={inputClass:"",rtl:!1,showInput:!0,groupLabel:"Groups",dataHtml:"html",dataText:"text",dataValue:"value",dataGroup:"group",dataDisabled:"disabled",filterPlaceholderText:"Type to filter",filterEmptyText:"No results",filterClearIcon:"material-close"};Le.select=function(e,t){function a(e){var t,a,s,n,i,o,l=0,r=0,c={};if(j={},Y={},$=[],S=[],ce.length=0,ae)ie.each(T,function(l,d){i=d[X.dataText]+"",a=d[X.dataHtml],o=d[X.dataValue],s=d[X.dataGroup],n={value:o,html:a,text:i,index:l,cssClass:le?"mbsc-sel-gr-itm":""},j[o]=n,e&&!v(i,e)||($.push(n),se&&(void 0===c[s]?(t={text:s,value:r,options:[],index:r},Y[r]=t,c[s]=r,S.push(t),r++):t=Y[c[s]],oe&&(n.index=t.options.length),n.group=c[s],t.options.push(n)),d[X.dataDisabled]&&ce.push(o))});else if(se){var d=!0;ie("optgroup",q).each(function(t){Y[t]={text:this.label,value:t,options:[],index:t},d=!0,ie("option",this).each(function(a){n={value:this.value,text:this.text,index:oe?a:l++,group:t,cssClass:le?"mbsc-sel-gr-itm":""},j[this.value]=n,e&&!v(this.text,e)||(d&&(S.push(Y[t]),d=!1),$.push(n),Y[t].options.push(n),this.disabled&&ce.push(this.value))})})}else ie("option",q).each(function(t){n={value:this.value,text:this.text,index:t},j[this.value]=n,e&&!v(this.text,e)||($.push(n),this.disabled&&ce.push(this.value))});L=X.defaultValue?X.defaultValue:$.length?$[0].value:"",le&&($=[],l=0,ie.each(Y,function(e,t){t.options.length&&(o="__group"+e,n={text:t.text,value:o,group:e,index:l++,cssClass:"mbsc-sel-gr"},j[o]=n,$.push(n),ce.push(n.value),ie.each(t.options,function(e,t){t.index=l++,$.push(t)}))})),z&&($.length?z.removeClass("mbsc-sel-empty-v"):z.addClass("mbsc-sel-empty-v"))}function r(e,t,a,s,n){var i,o=[];for(i=0;i<e.length;i++)o.push({value:e[i].value,display:e[i].html||e[i].text,cssClass:e[i].cssClass});return{circular:!1,multiple:t&&!s?1:s,cssClass:(t&&!s?"mbsc-sel-one":"")+" "+n,data:o,label:a}}function c(){return r(S,Z,X.groupLabel,!1,"mbsc-sel-gr-whl")}function d(){return r(oe&&Y[k]?Y[k].options:$,Z,ee,J,"")}function u(){var e=[[]];return ne&&(V=c(),G?e[0][A]=V:e[A]=[V]),W=d(),G?e[0][H]=W:e[H]=[W],e}function m(e){J&&(e&&l(e)&&(e=e.split(",")),ie.isArray(e)&&(e=e[0])),P=void 0===e||null===e||""===e?L:e,!j[P]&&$&&$.length&&(P=$[0].value),ne&&(k=j[P]?j[P].group:null)}function f(e){return O[e]||(j[e]?j[e].text:"")}function h(e,t,a){var s,n,i=[],o=a?t._selected:t._tempSelected;if(Z){for(s in o[H])i.push(f(s));return i.join(", ")}return n=e[H],f(n)}function b(){var t,a="",s=e.getVal(),n=X.formatValue.call(N,e.getArrayVal(),e,!0);if(X.filter&&"inline"==X.display||C.val(n),q.is("select")&&ae){if(J)for(t=0;t<s.length;t++)a+='<option value="'+s[t]+'">'+f(s[t])+"</option>";else a='<option value="'+(null===s?"":s)+'">'+n+"</option>";q.html(a)}N!==C[0]&&q.val(s)}function p(){var t={};t[H]=d(),F=!0,e.changeWheel(t)}function v(e,t){return t=t.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&"),e.match(new RegExp(t,"ig"))}function y(e){return X.data.dataField?e[X.data.dataField]:X.data.processResponse?X.data.processResponse(e):e}function _(t){var s={};a(t),m(P),X.wheels=u(),s[H]=W,e._tempWheelArray[H]=P,ne&&(s[A]=V,e._tempWheelArray[A]=k),e.changeWheel(s,0,!0),b()}function x(t){return e.trigger("onFilter",{filterText:t})}function w(t){t[A]!=k&&(k=t[A],P=Y[k].options[0].value,t[H]=P,oe?p():e.setArrayVal(t,!1,!1,!0,I))}var C,T,D,M,k,S,V,Y,A,E,P,$,W,H,F,L="",O={},I=1e3,N=this,q=ie(N),R=de({},e.settings),X=de(e.settings,qe,R),z=ie('<div class="mbsc-sel-empty">'+X.filterEmptyText+"</div>"),U=X.readonly,j={},B=X.layout||(/top|bottom|inline/.test(X.display)||X.filter?"liquid":""),G="liquid"==B||!X.touchUi,J=o(X.select)?X.select:"multiple"==X.select||q.prop("multiple"),Z=J||!(!X.filter&&!X.tapSelect)&&1,K=this.id+"_dummy",Q=ie('label[for="'+this.id+'"]').attr("for",K),ee=void 0!==X.label?X.label:Q.length?Q.text():q.attr("name"),te=X.group,ae=!!X.data,se=ae?!!X.group:ie("optgroup",q).length,ne=se&&te&&te.groupWheel!==!1,oe=se&&te&&ne&&te.clustered===!0,le=se&&(!te||te.header!==!1&&!oe),re=q.val()||(J?[]:[""]),ce=[];return e.setVal=function(t,a,s,n,o){if(Z&&(null===t||void 0===t||J||(t=[t]),t&&l(t)&&(t=t.split(",")),e._tempSelected[H]=i(t),n||(e._selected[H]=i(t)),t=t?t[0]:null,ne)){var r=j[t],c=r&&r.group;e._tempSelected[A]=i([c]),n||(e._selected[A]=i([c]))}e._setVal(t,a,s,n,o)},e.getVal=function(t,a){var s;return Z?(s=n(t?e._tempSelected[H]:e._selected[H]),s=J?s:s.length?s[0]:null):(s=t?e._tempWheelArray:e._hasValue?e._wheelArray:null,s=s?s[H]:null),J?s:void 0!==s?se&&a?[j[s]?j[s].group:null,s]:s:null},e.refresh=function(e,t,a){a=a||s,e?(T=e,E||(X.data=e)):ie.isArray(X.data)&&(T=X.data),!e&&E&&void 0===t?g(X.data.url,function(e){T=y(e),_(),a()},X.data.dataType):(_(t),a())},t.invalid||(X.invalid=ce),ne?(A=0,H=1):(A=-1,H=0),Z&&(J&&q.prop("multiple",!0),re&&l(re)&&(re=re.split(",")),e._selected[H]=i(re)),e._$input&&e._$input.remove(),q.next().is(".mbsc-select-input")?C=q.next().removeAttr("tabindex"):X.input?C=ie(X.input):(X.filter&&"inline"==X.display?e._$input=ie('<div class="mbsc-sel-input-wrap"><input type="text" id="'+K+'" class="mbsc-select-input mbsc-control '+X.inputClass+'" readonly /></div>'):(C=ie('<input type="text" id="'+K+'" class="mbsc-select-input mbsc-control '+X.inputClass+'" readonly />'),e._$input=C),X.showInput&&(e._$input.insertAfter(q),C||(C=e._$input.find("#"+K)))),e.attachShow(C.attr("placeholder",X.placeholder||"")),C[0]!==N&&q.addClass("mbsc-sel-hdn").attr("tabindex",-1).attr("data-enhance",!1),!Z||X.rows%2||(X.rows=X.rows-1),X.filter&&(D=X.filter.minLength||0),E=X.data&&X.data.url,E?e.refresh():(ae&&(T=X.data),a(),m(q.val())),{layout:B,headerText:!1,anchor:C,compClass:"mbsc-sc mbsc-sel"+(Z?" mbsc-sel-multi":""),setOnTap:!ne||[!1,!0],formatValue:h,tapSelect:Z,parseValue:function(e){return m(void 0===e?q.val():e),ne?[k,P]:[P]},validate:function(e){var t=e.index,a=[];return a[H]=X.invalid,oe&&!F&&void 0===t&&p(),F=!1,{disabled:a}},onRead:b,onFill:b,onMarkupReady:function(e,t){if(X.filter){var a,s,n,i=ie(".mbsc-fr-w",e.target),o=ie('<span class="mbsc-sel-filter-clear mbsc-ic mbsc-ic-'+X.filterClearIcon+'"></span>');"inline"==X.display?(a=C,C.parent().find(".mbsc-sel-filter-clear").remove()):(i.find(".mbsc-fr-c").before('<div class="mbsc-input mbsc-sel-filter-cont mbsc-control-w mbsc-'+X.theme+(X.baseTheme?" mbsc-"+X.baseTheme:"")+'"><span class="mbsc-input-wrap"><input tabindex="0" type="text" class="mbsc-sel-filter-input mbsc-control"/></span></div>'),a=i.find(".mbsc-sel-filter-input")),M=null,n=a[0],a.prop("readonly",!1).attr("placeholder",X.filterPlaceholderText).parent().append(o),i.find(".mbsc-fr-c").prepend(z),t._activeElm=n,t.tap(o,function(){M=null,n.value="",t.refresh(),o.removeClass("mbsc-sel-filter-show-clear"),x("")}),a.on("keydown",function(e){13!=e.keyCode&&27!=e.keyCode||(e.stopPropagation(),n.blur())}).on("input",function(){clearTimeout(s),n.value.length?o.addClass("mbsc-sel-filter-show-clear"):o.removeClass("mbsc-sel-filter-show-clear"),s=setTimeout(function(){M!==n.value&&x(n.value)!==!1&&(M=n.value,(M.length>=D||!M.length)&&(E&&X.data.remoteFilter?g(X.data.url+encodeURIComponent(M),function(e){t.refresh(y(e))},X.data.dataType):t.refresh(void 0,M)))},500)})}},onBeforeShow:function(){J&&X.counter&&(X.headerText=function(){var t=0;return ie.each(e._tempSelected[H],function(){t++}),(t>1?X.selectedPluralText||X.selectedText:X.selectedText).replace(/{count}/,t)}),m(q.val()),Z&&ne&&(e._selected[A]=i([k])),X.filter&&a(void 0),e.settings.wheels=u(),F=!0},onWheelGestureStart:function(e){e.index==A&&(X.readonly=[!1,!0])},onWheelAnimationEnd:function(t){var a=e.getArrayVal(!0);t.index==A?(X.readonly=U,Z||w(a)):t.index==H&&a[H]!=P&&(P=a[H],ne&&j[P]&&j[P].group!=k&&(k=j[P].group,a[A]=k,e._tempSelected[A]=i([k]),e.setArrayVal(a,!1,!1,!0,I)))},onItemTap:function(t){var a;if(t.index==H&&(O[t.value]=j[t.value].text,Z&&!J&&t.selected))return!1;if(t.index==A&&Z){if(t.selected)return!1;a=e.getArrayVal(!0),a[A]=t.value,w(a)}},onClose:function(){E&&X.data.remoteFilter&&M&&e.refresh()},onDestroy:function(){e._$input&&e._$input.remove(),q.removeClass("mbsc-sel-hdn").removeAttr("tabindex")}}},a("select",Ne);var Re=/^(\d{4}|[+\-]\d{6})(?:-(\d{2})(?:-(\d{2}))?)?(?:T(\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{3}))?)?((Z)|([+\-])(\d{2})(?::(\d{2}))?)?)?$/,Xe=/^((\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{3}))?)?(?:(Z)|([+\-])(\d{2})(?::(\d{2}))?)?)?$/,ze=/^\d{1,2}(\/\d{1,2})?$/,Ue=/^w\d$/i,je={shortYearCutoff:"+10",monthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],dayNames:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],dayNamesShort:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],dayNamesMin:["S","M","T","W","T","F","S"],amText:"am",pmText:"pm",getYear:function(e){return e.getFullYear()},getMonth:function(e){return e.getMonth()},getDay:function(e){return e.getDate()},getDate:k,getMaxDayOfMonth:function(e,t){return 32-new Date(e,t,32,12).getDate()},getWeekNumber:function(e){e=new Date(e),e.setHours(0,0,0),e.setDate(e.getDate()+4-(e.getDay()||7));var t=new Date(e.getFullYear(),0,1);return Math.ceil(((e-t)/864e5+1)/7)}};I.datetime={formatDate:S,parseDate:V};var Be={separator:" ",dateFormat:"mm/dd/yy",dateDisplay:"MMddyy",timeFormat:"h:ii A",dayText:"Day",monthText:"Month",yearText:"Year",hourText:"Hours",minuteText:"Minutes",ampmText:"&nbsp;",secText:"Seconds",nowText:"Now",todayText:"Today"},Ge=function(e){function t(e){var t,a,s,n,i=[];if(e){for(t=0;t<e.length;t++)if(a=e[t],a.start&&a.end&&!Xe.test(a.start))for(s=new Date(H(a.start,J,j)),n=new Date(H(a.end,J,j));s<=n;)i.push(k(s.getFullYear(),s.getMonth(),s.getDate())),s.setDate(s.getDate()+1);else i.push(a);return i}return e}function a(e,t,a,s){return Math.min(s,Math.floor(e/t)*t+a)}function s(e,t,a){return Math.floor((a-t)/e)*e+t}function n(e){return j.getYear(e)}function i(e){return j.getMonth(e)}function o(e){return j.getDay(e)}function l(e){var t=e.getHours();return t=re&&t>=12?t-12:t,a(t,me,pe,ye)}function r(e){return a(e.getMinutes(),fe,ve,_e)}function d(e){return a(e.getSeconds(),he,ge,xe)}function u(e){return e.getMilliseconds()}function m(e){return e.getHours()>11?1:0}function f(e){return e.getFullYear()+"-"+c(e.getMonth()+1)+"-"+c(e.getDate())}function h(e){return a(Math.round((e.getTime()-new Date(e).setHours(0,0,0,0))/1e3),$||1,0,86400)}function b(e,t,a,s){var n;return void 0===O[t]||(n=+e[O[t]],isNaN(n))?a?Me[t](a):void 0!==I[t]?I[t]:Me[t](s):n}function p(e){var t,a=new Date((new Date).setHours(0,0,0,0));if(null===e)return e;void 0!==O.dd&&(t=e[O.dd].split("-"),t=new Date(t[0],t[1]-1,t[2])),void 0!==O.tt&&(t=t||a,t=new Date(t.getTime()+e[O.tt]%86400*1e3));var s=b(e,"y",t,a),n=b(e,"m",t,a),i=Math.min(b(e,"d",t,a),j.getMaxDayOfMonth(s,n)),o=b(e,"h",t,a);return j.getDate(s,n,i,re&&b(e,"a",t,a)?o+12:o,b(e,"i",t,a),b(e,"s",t,a),b(e,"u",t,a))}function v(e,t){var a,s,n=["y","m","d","a","h","i","s","u","dd","tt"],i=[];if(null===e||void 0===e)return e;for(a=0;a<n.length;a++)s=n[a],void 0!==O[s]&&(i[O[s]]=Me[s](e)),t&&(I[s]=Me[s](e));return i}function g(e,t){return t?Math.floor(new Date(e)/864e5):e.getMonth()+12*(e.getFullYear()-1970)}function y(e){return{value:e,display:(/yy/i.test(Q)?e:(e+"").substr(2,2))+(j.yearSuffix||"")}}function _(e){return e}function x(e){var t=/d/i.test(e);return{label:"",cssClass:"mbsc-dt-whl-date",min:ne?g(f(ne),t):void 0,max:oe?g(f(oe),t):void 0,data:function(a){var s=new Date((new Date).setHours(0,0,0,0)),n=t?new Date(864e5*a):new Date(1970,a,1);return t&&(n=new Date(n.getUTCFullYear(),n.getUTCMonth(),n.getUTCDate())),{invalid:t&&!Y(n,!0),value:f(n),display:s.getTime()==n.getTime()?j.todayText:S(e,n,j)}},getIndex:function(e){return g(e,t)}}}function w(e){var t,a,s,n=[];for(/s/i.test(e)?a=he:/i/i.test(e)?a=60*fe:/h/i.test(e)&&(a=3600*me),$=Te.tt=a,t=0;t<86400;t+=a)s=new Date((new Date).setHours(0,0,0,0)+1e3*t),n.push({value:t,display:S(e,s,j)});return{label:"",cssClass:"mbsc-dt-whl-time",data:n}}function C(){var e,t,a,s,n,i,o,l,r=0,d=[],u=[],m=[];if(/date/i.test(B)){for(e=Z.split(/\|/.test(Z)?"|":""),s=0;s<e.length;s++)if(a=e[s],i=0,a.length)if(/y/i.test(a)&&(q.y=1,i++),/m/i.test(a)&&(q.y=1,q.m=1,i++),/d/i.test(a)&&(q.y=1,q.m=1,q.d=1,i++),i>1&&void 0===O.dd)O.dd=r,r++,u.push(x(a)),m=u,F=!0;else if(/y/i.test(a)&&void 0===O.y)O.y=r,r++,u.push({cssClass:"mbsc-dt-whl-y",label:j.yearText,min:ne?j.getYear(ne):void 0,max:oe?j.getYear(oe):void 0,data:y,getIndex:_});else if(/m/i.test(a)&&void 0===O.m){for(O.m=r,o=[],r++,n=0;n<12;n++)l=Q.replace(/[dy|]/gi,"").replace(/mm/,c(n+1)+(j.monthSuffix||"")).replace(/m/,n+1+(j.monthSuffix||"")),o.push({value:n,display:/MM/.test(l)?l.replace(/MM/,'<span class="mbsc-dt-month">'+j.monthNames[n]+"</span>"):l.replace(/M/,'<span class="mbsc-dt-month">'+j.monthNamesShort[n]+"</span>")});u.push({cssClass:"mbsc-dt-whl-m",label:j.monthText,data:o})}else if(/d/i.test(a)&&void 0===O.d){for(O.d=r,o=[],r++,n=1;n<32;n++)o.push({value:n,display:(/dd/i.test(Q)?c(n):n)+(j.daySuffix||"")});u.push({cssClass:"mbsc-dt-whl-d",label:j.dayText,data:o})}d.push(u)}if(/time/i.test(B)){for(t=K.split(/\|/.test(K)?"|":""),s=0;s<t.length;s++)if(a=t[s],i=0,a.length&&(/h/i.test(a)&&(q.h=1,i++),/i/i.test(a)&&(q.i=1,i++),/s/i.test(a)&&(q.s=1,i++),/a/i.test(a)&&i++),i>1&&void 0===O.tt)O.tt=r,r++,m.push(w(a));else if(/h/i.test(a)&&void 0===O.h){for(o=[],O.h=r,q.h=1,r++,n=pe;n<(re?12:24);n+=me)o.push({value:n,display:re&&0===n?12:/hh/i.test(ee)?c(n):n});m.push({cssClass:"mbsc-dt-whl-h",label:j.hourText,data:o})}else if(/i/i.test(a)&&void 0===O.i){for(o=[],O.i=r,q.i=1,r++,n=ve;n<60;n+=fe)o.push({value:n,display:/ii/i.test(ee)?c(n):n});m.push({cssClass:"mbsc-dt-whl-i",label:j.minuteText,data:o})}else if(/s/i.test(a)&&void 0===O.s){for(o=[],O.s=r,q.s=1,r++,n=ge;n<60;n+=he)o.push({value:n,display:/ss/i.test(ee)?c(n):n});m.push({cssClass:"mbsc-dt-whl-s",label:j.secText,data:o})}else/a/i.test(a)&&void 0===O.a&&(O.a=r,r++,m.push({cssClass:"mbsc-dt-whl-a",label:j.ampmText,data:/A/.test(a)?[{value:0,display:j.amText.toUpperCase()},{value:1,display:j.pmText.toUpperCase()}]:[{value:0,display:j.amText},{value:1,display:j.pmText}]}));m!=u&&d.push(m)}return d}function T(e){var t,a,s,n={};if(e.is("input")){switch(e.attr("type")){case"date":t="yy-mm-dd";break;case"datetime":t="yy-mm-ddTHH:ii:ssZ";break;case"datetime-local":t="yy-mm-ddTHH:ii:ss";break;case"month":t="yy-mm",n.dateOrder="mmyy";break;case"time":t="HH:ii:ss"}n.format=t,a=e.attr("min"),s=e.attr("max"),a&&"undefined"!=a&&(n.min=V(t,a)),s&&"undefined"!=s&&(n.max=V(t,s))}return n}function D(e,t){var a,s,n=!1,i=!1,o=0,l=0,r=ne?p(v(ne)):-(1/0),c=oe?p(v(oe)):1/0;if(Y(e))return e;if(e<r&&(e=r),e>c&&(e=c),a=e,s=e,2!==t)for(n=Y(a,!0);!n&&a<c&&o<100;)a=new Date(a.getTime()+864e5),n=Y(a,!0),o++;if(1!==t)for(i=Y(s,!0);!i&&s>r&&l<100;)s=new Date(s.getTime()-864e5),i=Y(s,!0),l++;return 1===t&&n?a:2===t&&i?s:M(e,a)?a:M(e,s)?s:l<=o&&i?s:a}function M(e,t){return j.getYear(e)===j.getYear(t)&&j.getMonth(e)===j.getMonth(t)}function Y(e,t){return!(!t&&e<ne)&&(!(!t&&e>oe)&&(!!A(e,se)||!A(e,ae)))}function A(e,t){var a,s,n;if(t)for(s=0;s<t.length;s++)if(a=t[s],n=a+"",!a.start)if(Ue.test(n)){if(n=+n.replace("w",""),n==e.getDay())return!0}else if(ze.test(n)){if(n=n.split("/"),n[1]){if(n[0]-1==e.getMonth()&&n[1]==e.getDate())return!0}else if(n[0]==e.getDate())return!0}else if(a=H(a,J,j),e.getFullYear()==a.getFullYear()&&e.getMonth()==a.getMonth()&&e.getDate()==a.getDate())return!0;return!1}function E(e,t,a,s,n,i,o){var l,r,c,d;if(e)for(r=0;r<e.length;r++)if(l=e[r],d=l+"",!l.start)if(Ue.test(d))for(d=+d.replace("w",""),c=d-s;c<n;c+=7)c>=0&&(i[c+1]=o);else ze.test(d)?(d=d.split("/"),d[1]?d[0]-1==a&&(i[d[1]]=o):i[d[0]]=o):(l=H(l,J,j),j.getYear(l)==t&&j.getMonth(l)==a&&(i[j.getDay(l)]=o))}function P(e,t,s,n,i,o,l,r){var c,d,u,m,f,h,b,p,v,g,y,_,x,w,C,T,D,M,k,S,V={},Y=j.getDate(n,i,o),A=["a","h","i","s"];if(e){for(b=0;b<e.length;b++)y=e[b],y.start&&(y.apply=!1,u=y.d,D=u+"",M=D.split("/"),u&&(u.getTime&&n==j.getYear(u)&&i==j.getMonth(u)&&o==j.getDay(u)||!Ue.test(D)&&(M[1]&&o==M[1]&&i==M[0]-1||!M[1]&&o==M[0])||Ue.test(D)&&Y.getDay()==+D.replace("w",""))&&(y.apply=!0,V[Y]=!0));for(b=0;b<e.length;b++)if(y=e[b],c=0,T=0,p=we[s],v=Ce[s],w=!0,C=!0,d=!1,y.start&&(y.apply||!y.d&&!V[Y])){for(_=y.start.split(":"),x=y.end.split(":"),g=0;g<3;g++)void 0===_[g]&&(_[g]=0),void 0===x[g]&&(x[g]=59),_[g]=+_[g],x[g]=+x[g];if("tt"==s)p=a(Math.round((new Date(Y).setHours(_[0],_[1],_[2])-new Date(Y).setHours(0,0,0,0))/1e3),$,0,86400),v=a(Math.round((new Date(Y).setHours(x[0],x[1],x[2])-new Date(Y).setHours(0,0,0,0))/1e3),$,0,86400);else{for(_.unshift(_[0]>11?1:0),x.unshift(x[0]>11?1:0),re&&(_[1]>=12&&(_[1]=_[1]-12),x[1]>=12&&(x[1]=x[1]-12)),g=0;g<t;g++)void 0!==R[g]&&(k=a(_[g],Te[A[g]],we[A[g]],Ce[A[g]]),S=a(x[g],Te[A[g]],we[A[g]],Ce[A[g]]),m=0,f=0,h=0,re&&1==g&&(m=_[0]?12:0,f=x[0]?12:0,h=R[0]?12:0),w||(k=0),C||(S=Ce[A[g]]),(w||C)&&k+m<R[g]+h&&R[g]+h<S+f&&(d=!0),R[g]!=k&&(w=!1),R[g]!=S&&(C=!1));if(!r)for(g=t+1;g<4;g++)_[g]>0&&(c=Te[s]),x[g]<Ce[A[g]]&&(T=Te[s]);d||(k=a(_[t],Te[s],we[s],Ce[s])+c,S=a(x[t],Te[s],we[s],Ce[s])-T,w&&(p=k),C&&(v=S))}if(w||C||d)for(g=p;g<=v;g+=Te[s])l[g]=!r}}}var $,F,L,O={},I={},q={},R=[],X=T(ie(this)),z=de({},e.settings),U=N[z.calendarSystem],j=de(e.settings,je,U,Be,X,z),B=j.preset,G="datetime"==B?j.dateFormat+j.separator+j.timeFormat:"time"==B?j.timeFormat:j.dateFormat,J=X.format||G,Z=j.dateWheels||j.dateFormat,K=j.timeWheels||j.timeFormat,Q=j.dateWheels||j.dateDisplay,ee=K,te=j.baseTheme||j.theme,ae=t(j.invalid),se=t(j.valid),ne=H(j.min,J,j),oe=H(j.max,J,j),le=/time/i.test(B),re=/h/.test(ee),ce=/D/.test(Q),ue=j.steps||{},me=ue.hour||j.stepHour||1,fe=ue.minute||j.stepMinute||1,he=ue.second||j.stepSecond||1,be=ue.zeroBased,pe=be||!ne?0:ne.getHours()%me,ve=be||!ne?0:ne.getMinutes()%fe,ge=be||!ne?0:ne.getSeconds()%he,ye=s(me,pe,re?11:23),_e=s(fe,ve,59),xe=s(fe,ve,59),we={y:ne?ne.getFullYear():-(1/0),m:0,d:1,h:pe,i:ve,s:ge,a:0,tt:0},Ce={y:oe?oe.getFullYear():1/0,m:11,d:31,h:ye,i:_e,s:xe,a:1,tt:86400},Te={y:1,m:1,d:1,h:me,i:fe,s:he,a:1,tt:1},De={bootstrap:46,ios:50,material:46,mobiscroll:46,windows:50},Me={y:n,m:i,d:o,h:l,i:r,s:d,u:u,a:m,dd:f,tt:h};return e.getVal=function(t){return e._hasValue||t?W(p(e.getArrayVal(t)),j,J):null},e.getDate=function(t){return e._hasValue||t?p(e.getArrayVal(t)):null},e.setDate=function(t,a,s,n,i){e.setArrayVal(v(t,!0),a,i,n,s)},L=C(),j.isoParts=q,e._format=G,e._order=O,e.handlers.now=function(){e.setDate(new Date,e.live,1e3,!0,!0)},e.buttons.now={text:j.nowText,icon:j.nowIcon,handler:"now"},{minWidth:F&&le?De[te]:void 0,compClass:"mbsc-dt mbsc-sc",wheels:L,headerText:!!j.headerText&&function(){return S(G,p(e.getArrayVal(!0)),j)},formatValue:function(e){return S(J,p(e),j)},parseValue:function(t){return t||(I={},e._hasValue=!1),v(H(t||j.defaultValue||new Date,J,j,q),!!t)},validate:function(t){var a,s,n,i,o=t.values,l=t.index,r=t.direction,c=j.wheels[0][O.d],d=D(p(o),r),u=v(d),m=[],f={},h=Me.y(d),b=Me.m(d),g=j.getMaxDayOfMonth(h,b),y=!0,_=!0;if(ie.each(["dd","y","m","d","tt","a","h","i","s"],function(e,t){var a=we[t],n=Ce[t],i=Me[t](d);if(m[O[t]]=[],y&&ne&&(a=Me[t](ne)),_&&oe&&(n=Me[t](oe)),i<a&&(i=a),i>n&&(i=n),"dd"!==t&&"tt"!==t&&(y&&(y=i==a),_&&(_=i==n)),void 0!==O[t]){if("y"!=t&&"dd"!=t)for(s=we[t];s<=Ce[t];s+=Te[t])(s<a||s>n)&&m[O[t]].push(s);if("d"==t){var o=j.getDate(h,b,1).getDay(),l={};
E(ae,h,b,o,g,l,1),E(se,h,b,o,g,l,0),ie.each(l,function(e,a){a&&m[O[t]].push(e)})}}}),le&&ie.each(["a","h","i","s","tt"],function(t,a){var s=Me[a](d),n=Me.d(d),i={};void 0!==O[a]&&(P(ae,t,a,h,b,n,i,0),P(se,t,a,h,b,n,i,1),ie.each(i,function(e,t){t&&m[O[a]].push(e)}),R[t]=e.getValidValue(O[a],s,r,i))}),c&&(c._length!==g||ce&&(void 0===l||l===O.y||l===O.m))){for(f[O.d]=c,c.data=[],a=1;a<=g;a++)i=j.getDate(h,b,a).getDay(),n=Q.replace(/[my|]/gi,"").replace(/dd/,(a<10?"0"+a:a)+(j.daySuffix||"")).replace(/d/,a+(j.daySuffix||"")),c.data.push({value:a,display:/DD/.test(n)?n.replace(/DD/,'<span class="mbsc-dt-day">'+j.dayNames[i]+"</span>"):n.replace(/D/,'<span class="mbsc-dt-day">'+j.dayNamesShort[i]+"</span>")});e._tempWheelArray[O.d]=u[O.d],e.changeWheel(f)}return{disabled:m,valid:u}}}},Je={controls:["calendar"],firstDay:0,weekDays:"short",maxMonthWidth:170,breakPointMd:768,months:1,pageBuffer:1,weeks:6,highlight:!0,outerMonthChange:!0,quickNav:!0,yearChange:!0,tabs:"auto",todayClass:"mbsc-cal-today",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left6",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right6",dateText:"Date",timeText:"Time",todayText:"Today",fromText:"Start",toText:"End",moreEventsText:"{count} more",prevMonthText:"Previous Month",nextMonthText:"Next Month",prevYearText:"Previous Year",nextYearText:"Next Year"},Ze=function(e){function t(t){t.hasClass("mbsc-cal-h")&&(t.removeClass("mbsc-cal-h"),e._onSelectShow())}function a(e){e.hasClass("mbsc-cal-h")||e.addClass("mbsc-cal-h")}function n(e){e.hasClass("mbsc-cal-h")?t(e):a(e)}function i(){var t,a,s;be={},pe=[],xe={},mt=e.trigger,K=ie(wt),s=de({},e.settings),dt=de(e.settings,Je,s),t=dt.controls.join(","),De=dt.firstDay,We=dt.rtl,it=dt.pageBuffer,bt=dt.weekCounter,ue=dt.weeks,$e=6==ue,He="vertical"==dt.calendarScroll,he="inline"==dt.display?K.is("div")?K:K.parent():e._$window,pt="full"==dt.weekDays?"":"min"==dt.weekDays?"Min":"Short",a=dt.layout||("inline"==dt.display||/top|bottom/.test(dt.display)&&dt.touchUi?"liquid":""),Pe="liquid"==a,fe=Pe?null:dt.calendarWidth,ct=We&&!He?-1:1,ve="mbsc-disabled "+(dt.disabledClass||""),ye="mbsc-selected "+(dt.selectedTabClass||""),ge="mbsc-selected "+(dt.selectedClass||""),Re=Math.max(1,Math.floor(((dt.calendarHeight||0)/ue-45)/18)),t.match(/calendar/)&&(be.calendar=1,Me=!0),t.match(/date/)&&!Me&&(be.date=1),t.match(/time/)&&(be.time=1),dt.controls.forEach(function(e){be[e]&&pe.push(e)}),Ye=dt.quickNav&&Me&&$e,vt=dt.yearChange&&$e,Pe&&Me&&"center"==dt.display&&(e._isFullScreen=!0),dt.layout=a,dt.preset=(be.date||Me?"date":"")+(be.time?"time":"")}function o(){tt=vt?dt.monthNamesShort:dt.monthNames,_t=dt.yearSuffix||"",et=(dt.dateWheels||dt.dateFormat).search(/m/i),gt=(dt.dateWheels||dt.dateFormat).search(/y/i),Te=e._format,dt.min&&(Be=A(H(dt.min,Te,dt)),Qe=dt.getYear(Be),Ke=dt.getMonth(Be),Ze=dt.getDate(12*Math.floor(Qe/12),0,1)),dt.max&&(Ne=A(H(dt.max,Te,dt)),je=dt.getYear(Ne),Xe=dt.getMonth(Ne),qe=dt.getDate(12*Math.floor(je/12),0,1))}function l(e,t,a){e[t]=e[t]||[],e[t].push(a)}function r(e,t,a){var s,n,i,o,r=dt.getYear(t),c=dt.getMonth(t),d={};return e&&ie.each(e,function(e,u){if(s=u.d||u.start||u,n=s+"",u.start&&u.end)for(o=A(H(u.start,Te,dt)),i=A(H(u.end,Te,dt));o<=i;)l(d,o,u),o=dt.getDate(dt.getYear(o),dt.getMonth(o),dt.getDay(o)+1);else if(Ue.test(n))for(o=R(t,!1,+n.replace("w",""));o<=a;)l(d,o,u),o=dt.getDate(dt.getYear(o),dt.getMonth(o),dt.getDay(o)+7);else if(ze.test(n))if(n=n.split("/"),n[1])for(o=dt.getDate(r,n[0]-1,n[1]);o<=a;)l(d,o,u),o=dt.getDate(dt.getYear(o)+1,dt.getMonth(o),n[1]);else for(o=dt.getDate(r,c,n[0]);o<=a;)l(d,o,u),o=dt.getDate(dt.getYear(o),dt.getMonth(o)+1,n[0]);else l(d,A(H(s,Te,dt)),u)}),d}function c(e){return!(e<Be)&&(!(e>Ne)&&(void 0===Ae[e]||void 0!==ft[e]))}function d(t){var a,s,n,i,o=!!Oe[t]&&Oe[t],l=!!Ie[t]&&Ie[t],r=l&&l[0].background?l[0].background:o&&o[0].background,c="";if(l)for(a=0;a<l.length;a++)c+=(l[a].cssClass||"")+" ";if(o){for(n='<div class="mbsc-cal-marks">',a=0;a<o.length;a++)s=o[a],c+=(s.cssClass||"")+" ",n+='<div class="mbsc-cal-mark"'+(s.color?' style="background:'+s.color+';"':"")+"></div>";n+="</div>"}return i={marked:o,background:r,cssClass:c,markup:xe[t]?xe[t].join(""):Se?n:""},de(i,e._getDayProps(t,i))}function m(e){return' style="'+(He?"transform: translateY("+100*e+"%)":"left:"+100*e*ct+"%")+'"'}function f(){ot="auto"==dt.months?Math.max(1,Math.min(3,Math.floor((fe||L(he))/280))):+dt.months,rt=ot+2*it,lt=0,He=He&&ot<2,ut=void 0===dt.showOuterDays?ot<2&&!He:dt.showOuterDays}function h(e){return X(e,ot-1)>Ne&&(e=X(Ne,1-ot)),e<Be&&(e=Be),e}function b(e,t,a){var s=e.color,n=e.text;return'<div data-id="'+e._id+'" data-index="'+t+'" class="mbsc-cal-txt" title="'+ie("<div>"+n+"</div>").text()+'"'+(s?' style="background:'+s+(a?";color:"+C(s):"")+';"':"")+">"+(a?n:"")+"</div>"}function p(t){var a=R(X(t,-lt-it),!1),s=R(X(t,-lt+ot+it-1),!1);s=dt.getDate(dt.getYear(s),dt.getMonth(s),dt.getDay(s)+7*ue),e._onGenMonth(a,s),Ae=r(dt.invalid,a,s),ft=r(dt.valid,a,s),Oe=r(dt.labels||dt.events||dt.marked,a,s),Ie=r(dt.colors,a,s),Le=e._labels||Oe||Ie,ke=dt.labels||e._labels,ke&&!function(){xe={};for(var e={},t=a,n=function(){t.getDay()==De&&(e={});for(var a=Re,s=Le[t]||[],n=s.length,i=[],o=void 0,l=void 0,r=0,c=0,d=0,u=void 0;r<a;)if(o=null,s.forEach(function(t,a){e[r]==t&&(o=t,l=a)}),r==a-1&&(c<n-1||n&&d==n&&!o)){var m=n-c,f=(m>1?dt.moreEventsPluralText||dt.moreEventsText:dt.moreEventsText).replace(/{count}/,m);m&&i.push('<div class="mbsc-cal-txt-more">'+f+"</div>"),o&&(e[r]=null,o._days.forEach(function(e){xe[e][r]='<div class="mbsc-cal-txt-more">'+dt.moreEventsText.replace(/{count}/,1)+"</div>"})),c++,r++}else if(o)l==d&&d++,F(t,H(o.end))&&(e[r]=null),i.push(b(o,l)),r++,c++,o._days.push(t);else if(d<n){var h=s[d],p=h.start&&H(h.start),v=h.end&&H(h.end),g=t.getDay(),y=De-g>0?7:0,_=v&&!F(p,v);p&&!F(t,p)&&g!=De||(void 0===h._id&&(h._id=xt++),_&&(e[r]=h),h._days=[t],u=_?100*Math.min(Y(t,A(v))+1,7+De-g-y):100,i.push(_?'<div class="mbsc-cal-txt-w" style="width:'+u+'%">'+b(h,d,!0)+"</div>"+b(h,d):b(h,d,!0)),r++,c++),d++}else i.push('<div class="mbsc-cal-txt-ph"></div>'),r++;xe[t]=i,t=dt.getDate(dt.getYear(t),dt.getMonth(t),dt.getDay(t)+1)};t<s;)n()}()}function v(e){var t=dt.getYear(e),a=dt.getMonth(e);_e=e,re=e,W(e),mt("onMonthChange",{year:t,month:a}),mt("onMonthLoading",{year:t,month:a}),mt("onPageChange",{firstDay:e}),mt("onPageLoading",{firstDay:e}),p(e)}function g(e){var t=dt.getYear(e),a=dt.getMonth(e);void 0===nt?y(e,t,a):M(e,nt,!0),S(re,Ce.focus),Ce.focus=!1}function y(e,t,a){var s=Ce.$scroller;ie(".mbsc-cal-slide",s).removeClass("mbsc-cal-slide-a"),ie(".mbsc-cal-slide",s).slice(it,it+ot).addClass("mbsc-cal-slide-a"),ke&&ie(".mbsc-cal-slide-a .mbsc-cal-txt",s).on("mouseenter",function(){var e=ie(this).attr("data-id");ie('.mbsc-cal-txt[data-id="'+e+'"]',s).addClass("mbsc-hover")}).on("mouseleave",function(){ie(".mbsc-cal-txt.mbsc-hover",s).removeClass("mbsc-hover")}),mt("onMonthLoaded",{year:t,month:a}),mt("onPageLoaded",{firstDay:e})}function _(){var e,t;return e='<div class="mbsc-cal-tabs-c"><div class="mbsc-cal-tabs" role="tablist">',pe.forEach(function(a,s){t=dt[("calendar"==a?"date":a)+"Text"],e+='<div role="tab" aria-controls="'+(wt.id+"-mbsc-pnl-"+s)+'" class="mbsc-cal-tab mbsc-fr-btn-e '+(s?"":ye)+'" data-control="'+a+'"'+(dt.tabLink?'><a href="#">'+t+"</a>":' tabindex="0">'+t)+"</div>"}),e+="</div></div>"}function x(){var e,t,a,s,n,i,o="",l=We?dt.btnCalNextClass:dt.btnCalPrevClass,r=We?dt.btnCalPrevClass:dt.btnCalNextClass;for(n='<div class="mbsc-cal-btn-w"><div data-step="-1" role="button" tabindex="0" aria-label="'+dt.prevMonthText+'" class="'+l+' mbsc-cal-prev mbsc-cal-prev-m mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div>',t=0;t<(ue?ot:1);t++)n+='<div role="button" class="mbsc-cal-month"></div>';if(n+='<div data-step="1" role="button" tabindex="0" aria-label="'+dt.nextMonthText+'" class="'+r+' mbsc-cal-next mbsc-cal-next-m mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div></div>',vt&&(o='<div class="mbsc-cal-btn-w"><div data-step="-12" role="button" tabindex="0" aria-label="'+dt.prevYearText+'" class="'+l+' mbsc-cal-prev mbsc-cal-prev-y mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div><div role="button" class="mbsc-cal-year"></div><div data-step="12" role="button" tabindex="0" aria-label="'+dt.nextYearText+'" class="'+r+' mbsc-cal-next mbsc-cal-next-y mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div></div>'),ue&&(i=M(_e)),e='<div class="mbsc-w-p mbsc-cal-c"><div class="mbsc-cal '+($e?"":" mbsc-cal-week-view")+(ot>1?" mbsc-cal-multi ":"")+(bt?" mbsc-cal-weeks ":"")+(He?" mbsc-cal-vertical":"")+(Se?" mbsc-cal-has-marks":"")+(ke?" mbsc-cal-has-labels":"")+(ut?"":" mbsc-cal-hide-diff ")+(dt.calendarClass||"")+'"'+(Pe?"":' style="width:'+(fe||280*ot)+'px;"')+'><div class="mbsc-cal-hdr">'+(gt<et||ot>1?o+n:n+o)+"</div>",ue){for(e+='<div class="mbsc-cal-body"><div class="mbsc-cal-day-picker"><div class="mbsc-cal-days-c">',a=0;a<ot;a++){for(e+='<div class="mbsc-cal-days">',t=0;t<7;t++)s=(t+De)%7,e+='<div class="mbsc-cal-week-day'+s+'" aria-label="'+dt.dayNames[s]+'">'+dt["dayNames"+pt][s]+"</div>";e+="</div>"}e+='</div><div class="mbsc-cal-scroll-c mbsc-cal-day-scroll-c '+(dt.calendarClass||"")+'"'+(dt.calendarHeight?' style="height:'+dt.calendarHeight+'px"':"")+'><div class="mbsc-cal-scroll" style="width:'+100/ot+'%">'+i+"</div></div>"}if(e+="</div>",Ye){for(e+='<div class="mbsc-cal-month-picker mbsc-cal-picker mbsc-cal-h"><div class="mbsc-cal-scroll-c '+(dt.calendarClass||"")+'"><div class="mbsc-cal-scroll">',t=0;t<3;t++){for(e+='<div class="mbsc-cal-slide"'+m(t-1)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">',a=0;a<12;a++)a&&a%3===0&&(e+='</div><div class="mbsc-cal-row">'),e+='<div role="gridcell"'+(1==t?' tabindex="-1" aria-label="'+dt.monthNames[a]+'" data-val="'+a+'"':"")+' class="mbsc-cal-cell'+(1==t?" mbsc-btn-e":"")+'"><div class="mbsc-cal-cell-i mbsc-cal-cell-txt">'+(1==t?dt.monthNamesShort[a]:"&nbsp;")+"</div></div>";e+="</div></div></div>"}for(e+="</div></div></div>",e+='<div class="mbsc-cal-year-picker mbsc-cal-picker mbsc-cal-h"><div class="mbsc-cal-scroll-c '+(dt.calendarClass||"")+'"><div class="mbsc-cal-scroll">',t=-1;t<2;t++)e+=w(z(_e,t),t);e+="</div></div></div>"}return e+="</div></div></div>"}function w(e,t){var a,s=dt.getYear(e),n='<div class="mbsc-cal-slide"'+m(t)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">';for(a=0;a<12;a++)a&&a%3===0&&(n+='</div><div class="mbsc-cal-row">'),n+='<div role="gridcell" tabindex="-1" aria-label="'+s+'" data-val="'+s+'" class="mbsc-cal-cell mbsc-btn-e '+(s<Qe||s>je?" mbsc-disabled ":"")+(s==dt.getYear(_e)?ge:"")+'"><div class="mbsc-cal-cell-i mbsc-cal-cell-txt">'+s+_t+"</div></div>",s++;return n+="</div></div></div>"}function D(t,a){var s,n,i,o,l,r,u,f,h,b,p,v,g,y,_,x,w=1,T=dt.getYear(t),D=dt.getMonth(t),M=dt.getDay(t),k=null!==dt.defaultValue||e._hasValue?e.getDate(!0):null,S=dt.getDate(T,D,M).getDay(),V=De-S>0?7:0,Y='<div class="mbsc-cal-slide"'+m(a)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">';for(x=0;x<7*ue;x++)_=x+De-V,s=dt.getDate(T,D,_-S+M),i=s.getFullYear(),o=s.getMonth(),l=s.getDate(),r=dt.getMonth(s),u=dt.getDay(s),y=dt.getMaxDayOfMonth(i,o),f=i+"-"+(o+1)+"-"+l,h=de({valid:c(s),selected:k&&F(k,s)},d(s)),b=h.valid,p=h.selected,n=h.cssClass,v=new Date(s).setHours(12,0,0,0)===(new Date).setHours(12,0,0,0),g=r!==D,we[f]=h,x&&x%7===0&&(Y+='</div><div class="mbsc-cal-row">'),bt&&x%7===0&&("month"==bt&&g&&w>1?w=1==l?1:2:"year"==bt&&(w=dt.getWeekNumber(dt.getDate(i,o,l+(7-De+1)%7))),Y+='<div role="gridcell" class="mbsc-cal-cell mbsc-cal-week-nr">'+w+"</div>",w++),Y+='<div role="gridcell" aria-label="'+(v?dt.todayText+", ":"")+dt.dayNames[s.getDay()]+", "+dt.monthNames[r]+" "+u+" "+(h.ariaLabel?", "+h.ariaLabel:"")+'"'+(g&&!ut?' aria-hidden="true"':' data-full="'+f+'"')+(p?' aria-selected="true"':"")+(b?' tabindex="-1"':' aria-disabled="true"')+' class="mbsc-cal-cell mbsc-cal-day mbsc-cal-day'+_%7+" "+(dt.dayClass||"")+" "+(p?ge:"")+(v?" "+dt.todayClass:"")+(n?" "+n:"")+(1==u?" mbsc-cal-day-first":"")+(u==y?" mbsc-cal-day-last":"")+(g?" mbsc-cal-day-diff":"")+(b?" mbsc-btn-e":" mbsc-disabled")+(h.marked?" mbsc-cal-day-marked":"")+(h.background?" mbsc-cal-day-colored":"")+'"><div class="mbsc-cal-cell-i mbsc-cal-day-i"><div class="mbsc-cal-day-date mbsc-cal-cell-txt"'+(h.background?' style="background:'+h.background+";color:"+C(h.background)+'"':"")+">"+u+"</div>"+(h.markup||"")+"</div></div>";return Y+="</div></div></div>"}function M(e,t,a){var s,n=dt.getYear(e),i=dt.getMonth(e),o=Ce?Ce.pos:0,l="";if(we={},ue)for(t||(mt("onMonthLoading",{year:n,month:i}),mt("onPageLoading",{firstDay:e})),p(e),s=0;s<rt;s++)l+=D(X(e,s-lt-it),o*ct+s-it);return nt=void 0,a&&Ce&&(Ce.$active=null,Ce.$scroller.html(l),y(e,n,i)),l}function S(e,t){if(Ce){var a=Ce.$active;a&&a.length&&(a[0].blur(),a.hasClass("mbsc-disabled")?a.removeAttr("tabindex"):a.attr("tabindex","-1")),Ce.$active=ie('.mbsc-cal-slide-a .mbsc-cal-day[data-full="'+E(e)+'"]',Ce.$scroller).attr("tabindex","0"),t&&Ce.$active.length&&Ce.$active[0].focus()}}function V(t){var a=Ce&&Ce.$scroller;dt.highlight&&Ce&&(ie(".mbsc-selected",a).removeClass(ge).removeAttr("aria-selected"),(null!==dt.defaultValue||e._hasValue)&&ie('.mbsc-cal-day[data-full="'+E(t)+'"]',a).addClass(ge).attr("aria-selected","true"))}function P(e,t){ie(".mbsc-selected",t).removeClass(ge).removeAttr("aria-selected"),ie('.mbsc-cal-cell[data-val="'+e+'"]',t).addClass(ge).attr("aria-selected","true")}function $(t,a,s,n){var i,o;ht&&(t<Be&&(t=Be),t>Ne&&(t=Ne),"calendar"!==ht&&Ve&&!a||(e._isSetDate=!a,Me&&ue&&(o=R(h(t),$e),st&&(t<X(_e,-lt)||t>=X(_e,ot-lt))&&(i=$e?dt.getMonth(o)-dt.getMonth(_e)+12*(dt.getYear(o)-dt.getYear(_e)):Math.floor(Y(_e,o)/(7*ue)),i&&(Ce.queue=[],Ce.focus=n&&s,U(Ce,i,s))),i&&s||S(t,n),a||V(t),$e||W(t,!0),re=t,st=!0),e._onSetDate(t,i),e._isSetDate=!1))}function W(e,t){var a,s,n,i=dt.getYear(e),o=dt.getMonth(e),l=i+_t;if(Ye){if(P(o,at.$scroller),P(i,yt.$scroller),U(yt,Math.floor(i/12)-Math.floor(dt.getYear(yt.first)/12),!0),ie(".mbsc-cal-cell",at.$scroller).removeClass("mbsc-disabled"),i===Qe)for(a=0;a<Ke;a++)ie('.mbsc-cal-cell[data-val="'+a+'"]',at.$scroller).addClass("mbsc-disabled");if(i===je)for(a=Xe+1;a<=12;a++)ie('.mbsc-cal-cell[data-val="'+a+'"]',at.$scroller).addClass("mbsc-disabled")}for(t||(O(ie(".mbsc-cal-prev-m",ee),X(e,-lt)<=Be),O(ie(".mbsc-cal-next-m",ee),X(e,ot-lt)>Ne),O(ie(".mbsc-cal-prev-y",ee),dt.getDate(i-1,o+1,1)<=Be),O(ie(".mbsc-cal-next-y",ee),dt.getDate(i+1,o,1)>Ne)),oe.attr("aria-label",i).html(l),a=0;a<ot;a++)e=dt.getDate(i,o-lt+a,1),s=dt.getYear(e),n=dt.getMonth(e),l=s+_t,te.eq(a).attr("aria-label",dt.monthNames[n]+(vt?"":" "+i)).html((!vt&&gt<et?l+" ":"")+tt[n]+(!vt&&gt>et?" "+l:""))}function O(e,t){t?e.addClass(ve).attr("aria-disabled","true"):e.removeClass(ve).removeAttr("aria-disabled")}function I(t,a){var s=e.getDate(!0),n=t[0],i=t.attr("data-full"),o=i?i.split("-"):[],l=k(o[0],o[1]-1,o[2]),r=k(l.getFullYear(),l.getMonth(),l.getDate(),s.getHours(),s.getMinutes(),s.getSeconds()),c=t.hasClass("mbsc-selected"),d=ie(a.target),u=d[0];if(ut||!t.hasClass("mbsc-cal-day-diff")){if(ke&&n.contains(u))for(;u!=n;){if(d.hasClass("mbsc-cal-txt")||d.hasClass("mbsc-cal-txt-more")){var m=d.attr("data-index"),f=Le[l];if(mt("onLabelTap",{date:r,domEvent:a,target:d[0],labels:f,label:f[m]})===!1)return;break}d=d.parent(),u=d[0]}mt("onDayChange",de(we[i],{date:r,target:n,selected:c}))===!1||dt.readonly||t.hasClass("mbsc-disabled")||e._selectDay(t,l,r,c)}}function N(e){a(ae),$(dt.getDate(dt.getYear(Ce.first),e.attr("data-val"),1),!0,!0)}function q(e){a(le),$(dt.getDate(e.attr("data-val"),dt.getMonth(Ce.first),1),!0,!0)}function R(e,t,a){var s=dt.getYear(e),n=dt.getMonth(e),i=e.getDay(),o=De-i>0?7:0;return t?dt.getDate(s,n,1):dt.getDate(s,n,(void 0===a?De:a)-o-i+dt.getDay(e))}function X(e,t){var a=dt.getYear(e),s=dt.getMonth(e),n=dt.getDay(e);return $e?dt.getDate(a,s+t,1):dt.getDate(a,s,n+t*ue*7)}function z(e,t){var a=12*Math.floor(dt.getYear(e)/12);return dt.getDate(a+12*t,0,1)}function U(t,a,s,n){a&&e._isVisible&&(t.queue.push(arguments),1==t.queue.length&&j(t,a,s,n))}function j(e,t,a,s){var n,i,o="",l=e.$scroller,r=e.buffer,c=e.offset,d=e.pages,u=e.total,m=e.first,f=e.genPage,h=e.getFirst,b=t>0?Math.min(t,r):Math.max(t,-r),p=e.pos*ct+b-t+c,v=Math.abs(t)>r;e.callback&&(e.load(),e.callback(!0)),e.first=h(m,t),e.pos+=b*ct,e.changing=!0,e.load=function(){if(v){for(n=0;n<d;n++)i=t+n-c,o+=f(h(m,i),p+i);t>0?(ie(".mbsc-cal-slide",l).slice(-d).remove(),l.append(o)):t<0&&(ie(".mbsc-cal-slide",l).slice(0,d).remove(),l.prepend(o))}},e.callback=function(a){var o=Math.abs(b),d="";for(n=0;n<o;n++)i=t+n-c-r+(t>0?u-o:0),d+=f(h(m,i),p+i);if(t>0?(l.append(d),ie(".mbsc-cal-slide",l).slice(0,b).remove()):t<0&&(l.prepend(d),ie(".mbsc-cal-slide",l).slice(b).remove()),v){for(d="",n=0;n<o;n++)i=t+n-c-r+(t>0?0:u-o),d+=f(h(m,i),p+i);t>0?(ie(".mbsc-cal-slide",l).slice(0,b).remove(),l.prepend(d)):t<0&&(ie(".mbsc-cal-slide",l).slice(b).remove(),l.append(d))}G(e),s&&!a&&s(),e.callback=null,e.load=null,e.queue.shift(),v=!1,e.queue.length?j.apply(this,e.queue[0]):(e.changing=!1,e.onAfterChange(e.first))},e.onBeforeChange(e.first),e.load&&(e.load(),e.scroller.scroll(-e.pos*e.size,a?200:0,!1,e.callback))}function B(t,a,s,n,i,o,l,r,c,d,m,f,h){var b=He?"Y":"X",p={$scroller:ie(".mbsc-cal-scroll",t),queue:[],buffer:n,offset:i,pages:o,first:r,total:l,pos:0,min:a,max:s,genPage:f,getFirst:h,onBeforeChange:d,onAfterChange:m};return p.scroller=new Fe(t,{axis:b,easing:"",contSize:0,maxSnapScroll:n,mousewheel:void 0===dt.mousewheel?He:dt.mousewheel,time:200,lock:!0,rtl:We,stopProp:!1,minScroll:0,maxScroll:0,onBtnTap:function(e){"touchend"==e.domEvent.type&&u(),c(ie(e.target),e.domEvent)},onAnimationStart:function(){p.changing=!0},onAnimationEnd:function(e){f&&U(p,Math.round((-p.pos*p.size-e["pos"+b])/p.size)*ct)}}),e._scrollers.push(p.scroller),p}function G(e,t){var a,s=0,n=0,i=e.first;if(!e.changing||!t){if(e.getFirst){for(s=e.buffer,n=e.buffer;n&&e.getFirst(i,n+e.pages-e.offset-1)>e.max;)n--;for(;s&&e.getFirst(i,1-s-e.offset)<=e.min;)s--}a=Math.round(me/e.pages),Pe&&a&&e.size!=a&&e.$scroller[He?"height":"width"](a),de(e.scroller.settings,{snap:a,minScroll:(-e.pos*ct-n)*a,maxScroll:(-e.pos*ct+s)*a}),e.size=a,e.scroller.refresh()}}function J(t){e._onRefresh(t),e._isVisible&&Me&&ue&&(Ce&&Ce.changing?nt=t:(M(_e,t,!0),S(re)))}function Z(){if(Me&&ue){var t=ie(".mbsc-cal-scroll-c",ee);Ce=B(t[0],Be,Ne,it,lt,ot,rt,_e,I,v,g,D,X),Ye&&(at=B(t[1],null,null,1,0,1,3,_e,N),yt=B(t[2],Ze,qe,1,0,1,3,_e,q,s,s,w,z),e.tap(te,function(){n(ae),a(le)}),e.tap(oe,function(){n(le),a(ae)})),T(ie(".mbsc-cal-btn",ee),function(e,t,a,s){U(Ce,t,!0,s)}),g(_e),null===dt.defaultValue&&!e._hasValue||e._multiple||(e._activeElm=Ce.$active[0]),Q.on("keydown",function(e){var t,a=dt.getYear(re),s=dt.getMonth(re),n=dt.getDay(re);switch(e.keyCode){case 32:I(Ce.$active,e);break;case 37:t=dt.getDate(a,s,n-1*ct);break;case 39:t=dt.getDate(a,s,n+1*ct);break;case 38:t=dt.getDate(a,s,n-7);break;case 40:t=dt.getDate(a,s,n+7);break;case 36:t=dt.getDate(a,s,1);break;case 35:t=dt.getDate(a,s+1,0);break;case 33:t=e.altKey?dt.getDate(a-1,s,n):$e?dt.getDate(a,s-1,n):dt.getDate(a,s,n-7*ue);break;case 34:t=e.altKey?dt.getDate(a+1,s,n):$e?dt.getDate(a,s+1,n):dt.getDate(a,s,n+7*ue)}t&&(e.preventDefault(),$(t,!0,!1,!0))})}e.tap(ie(".mbsc-cal-tab",ee),function(){e.changeTab(ie(this).attr("data-control"))})}var K,Q,ee,te,ae,se,ne,oe,le,re,ce,ue,me,fe,he,be,pe,ve,ge,ye,_e,xe,we,Ce,Te,De,Me,ke,Se,Ve,Ye,Ae,Ee,Pe,$e,We,He,Le,Oe,Ie,Ne,qe,Re,Xe,je,Be,Ze,Ke,Qe,et,tt,at,st,nt,it,ot,lt,rt,ct,dt,ut,mt,ft,ht,bt,pt,vt,gt,yt,_t,xt=1,wt=this;return i(),ce=Ge.call(this,e),o(),e.refresh=function(){J(!1)},e.redraw=function(){J(!0)},e.navigate=function(e,t){$(H(e,Te,dt),!0,t)},e.changeTab=function(t){e._isVisible&&be[t]&&ht!=t&&(ht=t,ie(".mbsc-cal-tab",ee).removeClass(ye).removeAttr("aria-selected"),ie('.mbsc-cal-tab[data-control="'+t+'"]',ee).addClass(ye).attr("aria-selected","true"),Ve&&(ne.addClass("mbsc-cal-h"),be[ht].removeClass("mbsc-cal-h")),"calendar"==ht&&$(e.getDate(!0),!1,!0),e._showDayPicker(),e.trigger("onTabChange",{tab:ht}))},e._checkSize=!0,e._onGenMonth=s,e._onSelectShow=s,e._onSetDate=s,e._onRefresh=s,e._getDayProps=s,e._prepareObj=r,e._showDayPicker=function(){Ye&&(a(le,!0),a(ae,!0))},e._selectDay=e.__selectDay=function(t,a,s){var n=e.live;st=dt.outerMonthChange,Ee=!0,e.setDate(s,n,1e3,!n,!0),n&&mt("onSet",{valueText:e._value})},de(ce,{labels:null,compClass:"mbsc-calendar mbsc-dt mbsc-sc",onMarkupReady:function(t){var a=0;ee=ie(t.target),se=ie(".mbsc-fr-c",ee),re=e.getDate(!0),me=0,Me&&(Se=!(!dt.marked&&!dt.data||dt.labels||dt.multiLabel||dt.showEventCount),st=!0,ht="calendar",f(),_e=R(h(re),$e),se.append(x()),te=ie(".mbsc-cal-month",ee),oe=ie(".mbsc-cal-year",ee),Q=ie(".mbsc-cal-day-scroll-c",ee)),Ye&&(le=ie(".mbsc-cal-year-picker",ee),ae=ie(".mbsc-cal-month-picker",ee)),ne=ie(".mbsc-w-p",ee),pe.length>1&&se.before(_()),["date","time","calendar"].forEach(function(e){be[e]?(be[e]=ne.eq(a),a++):"date"==e&&!be.date&&Me&&(ne.eq(a).remove(),a++)}),pe.forEach(function(e){se.append(be[e])}),!Me&&be.date&&be.date.css("position","relative"),e._scrollers=[],Z()},onShow:function(){Me&&ue&&W($e?_e:re)},onHide:function(){e._scrollers.forEach(function(e){e.destroy()}),we=null,Ce=null,at=null,yt=null,ht=null},onValidated:function(t){var a,s,n=t.index,i=e._order;s=e.getDate(!0),Ee?a="calendar":void 0!==n&&(a=i.dd==n||i.d==n||i.m==n||i.y==n?"date":"time"),mt("onSetDate",{date:s,control:a}),"time"!==a&&$(s,!1,!!t.time,Ee&&!e._multiple),Ee=!1},onPosition:function(t){var a,s,n,i,o,l,r,c=t.oldHeight,d=t.windowHeight;if(Ve=(t.hasTabs||dt.tabs===!0||dt.tabs!==!1&&Pe)&&pe.length>1,Pe&&(t.windowWidth>=dt.breakPointMd?ie(t.target).addClass("mbsc-fr-md"):ie(t.target).removeClass("mbsc-fr-md")),Ve?(ee.addClass("mbsc-cal-tabbed"),ht=ie(".mbsc-cal-tab.mbsc-selected",ee).attr("data-control"),ne.addClass("mbsc-cal-h"),be[ht].removeClass("mbsc-cal-h")):(ee.removeClass("mbsc-cal-tabbed"),ne.removeClass("mbsc-cal-h")),e._isFullScreen&&(Q.height(""),o=t.popup.offsetHeight,r=d-o+Q[0].offsetHeight,d>=o&&Q.height(r)),ke&&ue&&d!=c){var u=r||Q[0].offsetHeight,m=ie(".mbsc-cal-txt-ph")[0],f=m.offsetTop,h=m.offsetHeight,b=Math.max(1,Math.floor((u/ue-f)/(h+2)));Re!=b&&(Re=b,e.redraw())}if(Me&&ue){if(l=Pe||He||Ve?Q[0][He?"offsetHeight":"offsetWidth"]:fe||280*ot,i=l!=me,me=l,Pe&&i&&vt)for(tt=dt.maxMonthWidth>te[0].offsetWidth?dt.monthNamesShort:dt.monthNames,s=dt.getYear(_e),n=dt.getMonth(_e),a=0;a<ot;a++)te.eq(a).text(tt[dt.getMonth(dt.getDate(s,n-lt+a,1))]);i&&G(Ce,!0)}Ye&&i&&(G(at,!0),G(yt,!0))}})},Ke={};Le.calendar=function(e){function t(e){return k(e.getFullYear(),e.getMonth(),e.getDate())}function a(e){var a,s,n=null;if(v={},e&&e.length)for(s=0;s<e.length;s++)a=H(e[s],r,m,m.isoParts),n=n||a,v[t(a)]=a;return n}function s(){e.redraw()}var i,l,r,c,d,u=de({},e.settings),m=de(e.settings,Ke,u),f="mbsc-selected "+(m.selectedClass||""),h=m.defaultValue,b="multiple"==m.select||m.select>1||"week"==m.selectType,p=o(m.select)?m.select:1/0,v={};return i=Ze.call(this,e),c=void 0===m.firstSelectDay?m.firstDay:m.firstSelectDay,r=e._format,b&&a(h),e._multiple=b,e._getDayProps=function(e){return{selected:b?void 0!==v[e]:void 0}},e._selectDay=function(t,a,i,o){if(m.setOnDayTap&&"multiple"!=m.select&&"inline"!=m.display)return e.setDate(i),void e.select();if(b)if("week"==m.selectType){var r,d,u=a.getDay()-c;for(u=u<0?7+u:u,"multiple"!=m.select&&(v={}),r=0;r<7;r++)d=k(a.getFullYear(),a.getMonth(),a.getDate()-u+r),o?delete v[d]:n(v).length/7<p&&(v[d]=d);s()}else{var h=ie('.mbsc-cal-day[data-full="'+t.attr("data-full")+'"]',l);o?(h.removeClass(f).removeAttr("aria-selected"),delete v[a]):n(v).length<p&&(h.addClass(f).attr("aria-selected","true"),v[a]=a)}e.__selectDay(t,a,i)},e.setVal=function(t,n,i,o,l){b&&(t=a(t)),e._setVal(t,n,i,o,l),b&&s()},e.getVal=function(t){var a,s=[];if(b){for(a in v)s.push(W(v[a],m,r));return s}return W(e.getDate(t),m,r)},de({},i,{highlight:!b,outerMonthChange:!b,parseValue:function(e){return b&&e&&"string"==typeof e&&(e=a(e.split(","))),b&&h&&h.length&&(m.defaultValue=h[0]),i.parseValue.call(this,e)},formatValue:function(t){var a,s=[];if(b){for(a in v)s.push(S(r,v[a],m));return s.join(", ")}return i.formatValue.call(this,t,e)},onClear:function(){b&&(v={},s())},onBeforeShow:function(){void 0!==m.setOnDayTap||m.buttons&&m.buttons.length||1!=m.controls.length||(m.setOnDayTap=!0),m.setOnDayTap&&"inline"!=m.display&&(m.outerMonthChange=!1),m.counter&&b&&(m.headerText=function(){var e=0,t="week"==m.selectType?7:1;return ie.each(v,function(){e++}),e=Math.round(e/t),(e>1?m.selectedPluralText||m.selectedText:m.selectedText).replace(/{count}/,e)})},onMarkupReady:function(e){i.onMarkupReady.call(this,e),l=ie(e.target),b&&(ie(".mbsc-fr-hdr",l).attr("aria-live","off"),d=de({},v))},onCancel:function(){!e.live&&b&&(v=de({},d))}})},a("calendar",Ne),O.customTheme("mobiscroll-dark","mobiscroll");var Qe=O.themes,et="mobiscroll";return"android"==z?et="material":"ios"==z?et="ios":"wp"==z&&(et="windows"),ie.each(Qe.frame,function(e,t){return et&&t.baseTheme==et&&"mobiscroll-dark"!=e&&"material-dark"!=e&&"windows-dark"!=e&&"ios-dark"!=e?(O.autoTheme=e,!1):void(e==et&&(O.autoTheme=e))}),O});
var Effect,dynamicOptionListCount,dynamicOptionListObjects;String.prototype.parseColor=function(){var b='#',c,a;if(this.slice(0,4)=='rgb('){c=this.slice(4,this.length-1).split(','),a=0;do b+=parseInt(c[a]).toColorPart();while(++a<3)}else if(this.slice(0,1)=='#'){if(this.length==4)for(a=1;a<4;a++)b+=(this.charAt(a)+this.charAt(a)).toLowerCase();this.length==7&&(b=this.toLowerCase())}return b.length==7?b:arguments[0]||this},Element.collectTextNodes=function(a){return $A($(a).childNodes).collect(function(a){return a.nodeType==3?a.nodeValue:a.hasChildNodes()?Element.collectTextNodes(a):''}).flatten().join('')},Element.collectTextNodesIgnoreClass=function(b,a){return $A($(b).childNodes).collect(function(b){return b.nodeType==3?b.nodeValue:b.hasChildNodes()&&!Element.hasClassName(b,a)?Element.collectTextNodesIgnoreClass(b,a):''}).flatten().join('')},Element.setContentZoom=function(a,b){return a=$(a),a.setStyle({fontSize:b/100+'em'}),Prototype.Browser.WebKit&&window.scrollBy(0,0),a},Element.getInlineOpacity=function(a){return $(a).style.opacity||''},Element.forceRerendering=function(a){try{a=$(a);var b=document.createTextNode(' ');a.appendChild(b),a.removeChild(b)}catch(a){}},Effect={_elementDoesNotExistError:{name:'ElementDoesNotExistError',message:'The specified DOM element does not exist, but is required for this effect to operate'},Transitions:{linear:Prototype.K,sinoidal:function(a){return-Math.cos(a*Math.PI)/2+.5},reverse:function(a){return 1-a},flicker:function(a){var a=-Math.cos(a*Math.PI)/4+.75+Math.random()/4;return a>1?1:a},wobble:function(a){return-Math.cos(a*Math.PI*(9*a))/2+.5},pulse:function(a,b){return-Math.cos(a*((b||5)-.5)*2*Math.PI)/2+.5},spring:function(a){return 1-Math.cos(a*4.5*Math.PI)*Math.exp(-a*6)},none:function(a){return 0},full:function(a){return 1}},DefaultOptions:{duration:1,fps:100,sync:!1,from:0,to:1,delay:0,queue:'parallel'},tagifyText:function(a){var b='position:relative';Prototype.Browser.IE&&(b+=';zoom:1'),a=$(a),$A(a.childNodes).each(function(c){c.nodeType==3&&(c.nodeValue.toArray().each(function(d){a.insertBefore(new Element('span',{style:b}).update(d==' '?String.fromCharCode(160):d),c)}),Element.remove(c))})},multiple:function(a,d){var b,c,e;(typeof a=='object'||Object.isFunction(a))&&a.length?b=a:b=$(a).childNodes,c=Object.extend({speed:.1,delay:0},arguments[2]||{}),e=c.delay,$A(b).each(function(a,b){new d(a,Object.extend(c,{delay:b*c.speed+e}))})},PAIRS:{slide:['SlideDown','SlideUp'],blind:['BlindDown','BlindUp'],appear:['Appear','Fade']},toggle:function(a,b,c){return a=$(a),b=(b||'appear').toLowerCase(),Effect[Effect.PAIRS[b][a.visible()?1:0]](a,Object.extend({queue:{position:'end',scope:a.id||'global',limit:1}},c||{}))}},Effect.DefaultOptions.transition=Effect.Transitions.sinoidal,Effect.ScopedQueue=Class.create(Enumerable,{initialize:function(){this.effects=[],this.interval=null},_each:function(a){this.effects._each(a)},add:function(a){var b=(new Date).getTime(),c=Object.isString(a.options.queue)?a.options.queue:a.options.queue.position;switch(c){case'front':this.effects.findAll(function(a){return a.state=='idle'}).each(function(b){b.startOn+=a.finishOn,b.finishOn+=a.finishOn});break;case'with-last':b=this.effects.pluck('startOn').max()||b;break;case'end':b=this.effects.pluck('finishOn').max()||b;break}a.startOn+=b,a.finishOn+=b,(!a.options.queue.limit||this.effects.length<a.options.queue.limit)&&this.effects.push(a),this.interval||(this.interval=setInterval(this.loop.bind(this),15))},remove:function(a){this.effects=this.effects.reject(function(b){return b==a}),this.effects.length==0&&(clearInterval(this.interval),this.interval=null)},loop:function(){for(var b=(new Date).getTime(),a=0,c=this.effects.length;a<c;a++)this.effects[a]&&this.effects[a].loop(b)}}),Effect.Queues={instances:$H(),get:function(a){return Object.isString(a)?this.instances.get(a)||this.instances.set(a,new Effect.ScopedQueue):a}},Effect.Queue=Effect.Queues.get('global'),Effect.Base=Class.create({position:null,start:function(a){a&&a.transition===!1&&(a.transition=Effect.Transitions.linear),this.options=Object.extend(Object.extend({},Effect.DefaultOptions),a||{}),this.currentFrame=0,this.state='idle',this.startOn=this.options.delay*1e3,this.finishOn=this.startOn+this.options.duration*1e3,this.fromToDelta=this.options.to-this.options.from,this.totalTime=this.finishOn-this.startOn,this.totalFrames=this.options.fps*this.options.duration,this.render=function(){function a(a,b){a.options[b+'Internal']&&a.options[b+'Internal'](a),a.options[b]&&a.options[b](a)}return function(b){this.state==="idle"&&(this.state="running",a(this,'beforeSetup'),this.setup&&this.setup(),a(this,'afterSetup')),this.state==="running"&&(b=this.options.transition(b)*this.fromToDelta+this.options.from,this.position=b,a(this,'beforeUpdate'),this.update&&this.update(b),a(this,'afterUpdate'))}}(),this.event('beforeStart'),this.options.sync||Effect.Queues.get(Object.isString(this.options.queue)?'global':this.options.queue.scope).add(this)},loop:function(a){if(a>=this.startOn){if(a>=this.finishOn){this.render(1),this.cancel(),this.event('beforeFinish'),this.finish&&this.finish(),this.event('afterFinish');return}var b=(a-this.startOn)/this.totalTime,c=(b*this.totalFrames).round();c>this.currentFrame&&(this.render(b),this.currentFrame=c)}},cancel:function(){this.options.sync||Effect.Queues.get(Object.isString(this.options.queue)?'global':this.options.queue.scope).remove(this),this.state='finished'},event:function(a){this.options[a+'Internal']&&this.options[a+'Internal'](this),this.options[a]&&this.options[a](this)},inspect:function(){var a=$H();for(property in this)Object.isFunction(this[property])||a.set(property,this[property]);return'#<Effect:'+a.inspect()+',options:'+$H(this.options).inspect()+'>'}}),Effect.Parallel=Class.create(Effect.Base,{initialize:function(a){this.effects=a||[],this.start(arguments[1])},update:function(a){this.effects.invoke('render',a)},finish:function(a){this.effects.each(function(b){b.render(1),b.cancel(),b.event('beforeFinish'),b.finish&&b.finish(a),b.event('afterFinish')})}}),Effect.Tween=Class.create(Effect.Base,{initialize:function(a,d,e){a=Object.isString(a)?$(a):a;var c=$A(arguments),b=c.last(),f=c.length==5?c[3]:null;this.method=Object.isFunction(b)?b.bind(a):Object.isFunction(a[b])?a[b].bind(a):function(c){a[b]=c},this.start(Object.extend({from:d,to:e},f||{}))},update:function(a){this.method(a)}}),Effect.Event=Class.create(Effect.Base,{initialize:function(){this.start(Object.extend({duration:0},arguments[0]||{}))},update:Prototype.emptyFunction}),Effect.Opacity=Class.create(Effect.Base,{initialize:function(a){if(this.element=$(a),!this.element)throw Effect._elementDoesNotExistError;Prototype.Browser.IE&&!this.element.currentStyle.hasLayout&&this.element.setStyle({zoom:1});var b=Object.extend({from:this.element.getOpacity()||0,to:1},arguments[1]||{});this.start(b)},update:function(a){this.element.setOpacity(a)}}),Effect.Move=Class.create(Effect.Base,{initialize:function(a){if(this.element=$(a),!this.element)throw Effect._elementDoesNotExistError;var b=Object.extend({x:0,y:0,mode:'relative'},arguments[1]||{});this.start(b)},setup:function(){this.element.makePositioned(),this.originalLeft=parseFloat(this.element.getStyle('left')||'0'),this.originalTop=parseFloat(this.element.getStyle('top')||'0'),this.options.mode=='absolute'&&(this.options.x=this.options.x-this.originalLeft,this.options.y=this.options.y-this.originalTop)},update:function(a){this.element.setStyle({left:(this.options.x*a+this.originalLeft).round()+'px',top:(this.options.y*a+this.originalTop).round()+'px'})}}),Effect.MoveBy=function(a,b,c){return new Effect.Move(a,Object.extend({x:c,y:b},arguments[3]||{}))},Effect.Scale=Class.create(Effect.Base,{initialize:function(a,b){if(this.element=$(a),!this.element)throw Effect._elementDoesNotExistError;var c=Object.extend({scaleX:!0,scaleY:!0,scaleContent:!0,scaleFromCenter:!1,scaleMode:'box',scaleFrom:100,scaleTo:b},arguments[2]||{});this.start(c)},setup:function(){this.restoreAfterFinish=this.options.restoreAfterFinish||!1,this.elementPositioning=this.element.getStyle('position'),this.originalStyle={},['top','left','width','height','fontSize'].each(function(a){this.originalStyle[a]=this.element.style[a]}.bind(this)),this.originalTop=this.element.offsetTop,this.originalLeft=this.element.offsetLeft;var a=this.element.getStyle('font-size')||'100%';['em','px','%','pt'].each(function(b){a.indexOf(b)>0&&(this.fontSize=parseFloat(a),this.fontSizeType=b)}.bind(this)),this.factor=(this.options.scaleTo-this.options.scaleFrom)/100,this.dims=null,this.options.scaleMode=='box'&&(this.dims=[this.element.offsetHeight,this.element.offsetWidth]),/^content/.test(this.options.scaleMode)&&(this.dims=[this.element.scrollHeight,this.element.scrollWidth]),this.dims||(this.dims=[this.options.scaleMode.originalHeight,this.options.scaleMode.originalWidth])},update:function(b){var a=this.options.scaleFrom/100+this.factor*b;this.options.scaleContent&&this.fontSize&&this.element.setStyle({fontSize:this.fontSize*a+this.fontSizeType}),this.setDimensions(this.dims[0]*a,this.dims[1]*a)},finish:function(a){this.restoreAfterFinish&&this.element.setStyle(this.originalStyle)},setDimensions:function(b,c){var a={},d,e;this.options.scaleX&&(a.width=c.round()+'px'),this.options.scaleY&&(a.height=b.round()+'px'),this.options.scaleFromCenter&&(d=(b-this.dims[0])/2,e=(c-this.dims[1])/2,this.elementPositioning=='absolute'?(this.options.scaleY&&(a.top=this.originalTop-d+'px'),this.options.scaleX&&(a.left=this.originalLeft-e+'px')):(this.options.scaleY&&(a.top=-d+'px'),this.options.scaleX&&(a.left=-e+'px'))),this.element.setStyle(a)}}),Effect.Highlight=Class.create(Effect.Base,{initialize:function(a){if(this.element=$(a),!this.element)throw Effect._elementDoesNotExistError;var b=Object.extend({startcolor:'#ffff99'},arguments[1]||{});this.start(b)},setup:function(){if(this.element.getStyle('display')=='none'){this.cancel();return}this.oldStyle={},this.options.keepBackgroundImage||(this.oldStyle.backgroundImage=this.element.getStyle('background-image'),this.element.setStyle({backgroundImage:'none'})),this.options.endcolor||(this.options.endcolor=this.element.getStyle('background-color').parseColor('#ffffff')),this.options.restorecolor||(this.options.restorecolor=this.element.getStyle('background-color')),this._base=$R(0,2).map(function(a){return parseInt(this.options.startcolor.slice(a*2+1,a*2+3),16)}.bind(this)),this._delta=$R(0,2).map(function(a){return parseInt(this.options.endcolor.slice(a*2+1,a*2+3),16)-this._base[a]}.bind(this))},update:function(a){this.element.setStyle({backgroundColor:$R(0,2).inject('#',function(c,d,b){return c+(this._base[b]+this._delta[b]*a).round().toColorPart()}.bind(this))})},finish:function(){this.element.setStyle(Object.extend(this.oldStyle,{backgroundColor:this.options.restorecolor}))}}),Effect.ScrollTo=function(d){var a=arguments[1]||{},b=document.viewport.getScrollOffsets(),c=$(d).cumulativeOffset();return a.offset&&(c[1]+=a.offset),new Effect.Tween(null,b.top,c[1],a,function(a){scrollTo(b.left,a.round())})},Effect.Fade=function(a){var b,c;return a=$(a),b=a.getInlineOpacity(),c=Object.extend({from:a.getOpacity()||1,to:0,afterFinishInternal:function(a){if(a.options.to!=0)return;a.element.hide().setStyle({opacity:b})}},arguments[1]||{}),new Effect.Opacity(a,c)},Effect.Appear=function(a){a=$(a);var b=Object.extend({from:a.getStyle('display')=='none'?0:a.getOpacity()||0,to:1,afterFinishInternal:function(a){a.element.forceRerendering()},beforeSetup:function(a){a.element.setOpacity(a.options.from).show()}},arguments[1]||{});return new Effect.Opacity(a,b)},Effect.Puff=function(a){a=$(a);var b={opacity:a.getInlineOpacity(),position:a.getStyle('position'),top:a.style.top,left:a.style.left,width:a.style.width,height:a.style.height};return new Effect.Parallel([new Effect.Scale(a,200,{sync:!0,scaleFromCenter:!0,scaleContent:!0,restoreAfterFinish:!0}),new Effect.Opacity(a,{sync:!0,to:0})],Object.extend({duration:1,beforeSetupInternal:function(a){Position.absolutize(a.effects[0].element)},afterFinishInternal:function(a){a.effects[0].element.hide().setStyle(b)}},arguments[1]||{}))},Effect.BlindUp=function(a){return a=$(a),a.makeClipping(),new Effect.Scale(a,0,Object.extend({scaleContent:!1,scaleX:!1,restoreAfterFinish:!0,afterFinishInternal:function(a){a.element.hide().undoClipping()}},arguments[1]||{}))},Effect.BlindDown=function(a){a=$(a);var b=a.getDimensions();return new Effect.Scale(a,100,Object.extend({scaleContent:!1,scaleX:!1,scaleFrom:0,scaleMode:{originalHeight:b.height,originalWidth:b.width},restoreAfterFinish:!0,afterSetup:function(a){a.element.makeClipping().setStyle({height:'0px'}).show()},afterFinishInternal:function(a){a.element.undoClipping()}},arguments[1]||{}))},Effect.SwitchOff=function(a){a=$(a);var b=a.getInlineOpacity();return new Effect.Appear(a,Object.extend({duration:.4,from:0,transition:Effect.Transitions.flicker,afterFinishInternal:function(a){new Effect.Scale(a.element,1,{duration:.3,scaleFromCenter:!0,scaleX:!1,scaleContent:!1,restoreAfterFinish:!0,beforeSetup:function(a){a.element.makePositioned().makeClipping()},afterFinishInternal:function(a){a.element.hide().undoClipping().undoPositioned().setStyle({opacity:b})}})}},arguments[1]||{}))},Effect.DropOut=function(a){a=$(a);var b={top:a.getStyle('top'),left:a.getStyle('left'),opacity:a.getInlineOpacity()};return new Effect.Parallel([new Effect.Move(a,{x:0,y:100,sync:!0}),new Effect.Opacity(a,{sync:!0,to:0})],Object.extend({duration:.5,beforeSetup:function(a){a.effects[0].element.makePositioned()},afterFinishInternal:function(a){a.effects[0].element.hide().undoPositioned().setStyle(b)}},arguments[1]||{}))},Effect.Shake=function(c){var d,a,b,e;return c=$(c),d=Object.extend({distance:20,duration:.5},arguments[1]||{}),a=parseFloat(d.distance),b=parseFloat(d.duration)/10,e={top:c.getStyle('top'),left:c.getStyle('left')},new Effect.Move(c,{x:a,y:0,duration:b,afterFinishInternal:function(c){new Effect.Move(c.element,{x:-a*2,y:0,duration:b*2,afterFinishInternal:function(c){new Effect.Move(c.element,{x:a*2,y:0,duration:b*2,afterFinishInternal:function(c){new Effect.Move(c.element,{x:-a*2,y:0,duration:b*2,afterFinishInternal:function(c){new Effect.Move(c.element,{x:a*2,y:0,duration:b*2,afterFinishInternal:function(c){new Effect.Move(c.element,{x:-a,y:0,duration:b,afterFinishInternal:function(a){a.element.undoPositioned().setStyle(e)}})}})}})}})}})}})},Effect.SlideDown=function(a){var c,b;return a=$(a).cleanWhitespace(),c=a.down().getStyle('bottom'),b=a.getDimensions(),new Effect.Scale(a,100,Object.extend({scaleContent:!1,scaleX:!1,scaleFrom:window.opera?0:1,scaleMode:{originalHeight:b.height,originalWidth:b.width},restoreAfterFinish:!0,afterSetup:function(a){a.element.makePositioned(),a.element.down().makePositioned(),window.opera&&a.element.setStyle({top:''}),a.element.makeClipping().setStyle({height:'0px'}).show()},afterUpdateInternal:function(a){a.element.down().setStyle({bottom:a.dims[0]-a.element.clientHeight+'px'})},afterFinishInternal:function(a){a.element.undoClipping().undoPositioned(),a.element.down().undoPositioned().setStyle({bottom:c})}},arguments[1]||{}))},Effect.SlideUp=function(a){var c,b;return a=$(a).cleanWhitespace(),c=a.down().getStyle('bottom'),b=a.getDimensions(),new Effect.Scale(a,window.opera?0:1,Object.extend({scaleContent:!1,scaleX:!1,scaleMode:'box',scaleFrom:100,scaleMode:{originalHeight:b.height,originalWidth:b.width},restoreAfterFinish:!0,afterSetup:function(a){a.element.makePositioned(),a.element.down().makePositioned(),window.opera&&a.element.setStyle({top:''}),a.element.makeClipping().show()},afterUpdateInternal:function(a){a.element.down().setStyle({bottom:a.dims[0]-a.element.clientHeight+'px'})},afterFinishInternal:function(a){a.element.hide().undoClipping().undoPositioned(),a.element.down().undoPositioned().setStyle({bottom:c})}},arguments[1]||{}))},Effect.Squish=function(a){return new Effect.Scale(a,window.opera?1:0,{restoreAfterFinish:!0,beforeSetup:function(a){a.element.makeClipping()},afterFinishInternal:function(a){a.element.hide().undoClipping()}})},Effect.Grow=function(b){var g,h,a,d,e,f,c;switch(b=$(b),g=Object.extend({direction:'center',moveTransition:Effect.Transitions.sinoidal,scaleTransition:Effect.Transitions.sinoidal,opacityTransition:Effect.Transitions.full},arguments[1]||{}),h={top:b.style.top,left:b.style.left,height:b.style.height,width:b.style.width,opacity:b.getInlineOpacity()},a=b.getDimensions(),g.direction){case'top-left':d=e=f=c=0;break;case'top-right':d=a.width,e=c=0,f=-a.width;break;case'bottom-left':d=f=0,e=a.height,c=-a.height;break;case'bottom-right':d=a.width,e=a.height,f=-a.width,c=-a.height;break;case'center':d=a.width/2,e=a.height/2,f=-a.width/2,c=-a.height/2;break}return new Effect.Move(b,{x:d,y:e,duration:.01,beforeSetup:function(a){a.element.hide().makeClipping().makePositioned()},afterFinishInternal:function(b){new Effect.Parallel([new Effect.Opacity(b.element,{sync:!0,to:1,from:0,transition:g.opacityTransition}),new Effect.Move(b.element,{x:f,y:c,sync:!0,transition:g.moveTransition}),new Effect.Scale(b.element,100,{scaleMode:{originalHeight:a.height,originalWidth:a.width},sync:!0,scaleFrom:window.opera?1:0,transition:g.scaleTransition,restoreAfterFinish:!0})],Object.extend({beforeSetup:function(a){a.effects[0].element.setStyle({height:'0px'}).show()},afterFinishInternal:function(a){a.effects[0].element.undoClipping().undoPositioned().setStyle(h)}},g))}})},Effect.Shrink=function(a){var e,f,b,c,d;switch(a=$(a),e=Object.extend({direction:'center',moveTransition:Effect.Transitions.sinoidal,scaleTransition:Effect.Transitions.sinoidal,opacityTransition:Effect.Transitions.none},arguments[1]||{}),f={top:a.style.top,left:a.style.left,height:a.style.height,width:a.style.width,opacity:a.getInlineOpacity()},b=a.getDimensions(),e.direction){case'top-left':c=d=0;break;case'top-right':c=b.width,d=0;break;case'bottom-left':c=0,d=b.height;break;case'bottom-right':c=b.width,d=b.height;break;case'center':c=b.width/2,d=b.height/2;break}return new Effect.Parallel([new Effect.Opacity(a,{sync:!0,to:0,from:1,transition:e.opacityTransition}),new Effect.Scale(a,window.opera?1:0,{sync:!0,transition:e.scaleTransition,restoreAfterFinish:!0}),new Effect.Move(a,{x:c,y:d,sync:!0,transition:e.moveTransition})],Object.extend({beforeStartInternal:function(a){a.effects[0].element.makePositioned().makeClipping()},afterFinishInternal:function(a){a.effects[0].element.hide().undoClipping().undoPositioned().setStyle(f)}},e))},Effect.Pulsate=function(a){a=$(a);var b=arguments[1]||{},c=a.getInlineOpacity(),d=b.transition||Effect.Transitions.linear,e=function(a){return 1-d(-Math.cos(a*(b.pulses||5)*2*Math.PI)/2+.5)};return new Effect.Opacity(a,Object.extend(Object.extend({duration:2,from:0,afterFinishInternal:function(a){a.element.setStyle({opacity:c})}},b),{transition:e}))},Effect.Fold=function(a){a=$(a);var b={top:a.style.top,left:a.style.left,width:a.style.width,height:a.style.height};return a.makeClipping(),new Effect.Scale(a,5,Object.extend({scaleContent:!1,scaleX:!1,afterFinishInternal:function(c){new Effect.Scale(a,1,{scaleContent:!1,scaleY:!1,afterFinishInternal:function(a){a.element.hide().undoClipping().setStyle(b)}})}},arguments[1]||{}))},Effect.Morph=Class.create(Effect.Base,{initialize:function(c){var a,b;if(this.element=$(c),!this.element)throw Effect._elementDoesNotExistError;a=Object.extend({style:{}},arguments[1]||{}),Object.isString(a.style)?a.style.include(':')?this.style=a.style.parseStyle():(this.element.addClassName(a.style),this.style=$H(this.element.getStyles()),this.element.removeClassName(a.style),b=this.element.getStyles(),this.style=this.style.reject(function(a){return a.value==b[a.key]}),a.afterFinishInternal=function(a){a.element.addClassName(a.options.style),a.transforms.each(function(b){a.element.style[b.style]=''})}):this.style=$H(a.style),this.start(a)},setup:function(){function a(a){return(!a||['rgba(0, 0, 0, 0)','transparent'].include(a))&&(a='#ffffff'),a=a.parseColor(),$R(0,2).map(function(b){return parseInt(a.slice(b*2+1,b*2+3),16)})}this.transforms=this.style.map(function(f){var d=f[0],b=f[1],c=null,e,g;return b.parseColor('#zzzzzz')!='#zzzzzz'?(b=b.parseColor(),c='color'):d=='opacity'?(b=parseFloat(b),Prototype.Browser.IE&&!this.element.currentStyle.hasLayout&&this.element.setStyle({zoom:1})):Element.CSS_LENGTH.test(b)&&(e=b.match(/^([\+\-]?[0-9\.]+)(.*)$/),b=parseFloat(e[1]),c=e.length==3?e[2]:null),g=this.element.getStyle(d),{style:d.camelize(),originalValue:c=='color'?a(g):parseFloat(g||0),targetValue:c=='color'?a(b):b,unit:c}}.bind(this)).reject(function(a){return a.originalValue==a.targetValue||a.unit!='color'&&(isNaN(a.originalValue)||isNaN(a.targetValue))})},update:function(b){for(var c={},a,d=this.transforms.length;d--;)c[(a=this.transforms[d]).style]=a.unit=='color'?'#'+Math.round(a.originalValue[0]+(a.targetValue[0]-a.originalValue[0])*b).toColorPart()+Math.round(a.originalValue[1]+(a.targetValue[1]-a.originalValue[1])*b).toColorPart()+Math.round(a.originalValue[2]+(a.targetValue[2]-a.originalValue[2])*b).toColorPart():(a.originalValue+(a.targetValue-a.originalValue)*b).toFixed(3)+(a.unit===null?'':a.unit);this.element.setStyle(c,!0)}}),Effect.Transform=Class.create({initialize:function(a){this.tracks=[],this.options=arguments[1]||{},this.addTracks(a)},addTracks:function(a){return a.each(function(a){a=$H(a);var b=a.values().first();this.tracks.push($H({ids:a.keys().first(),effect:Effect.Morph,options:{style:b}}))}.bind(this)),this},play:function(){return new Effect.Parallel(this.tracks.map(function(a){var b=a.get('ids'),c=a.get('effect'),d=a.get('options'),e=[$(b)||$$(b)].flatten();return e.map(function(a){return new c(a,Object.extend({sync:!0},d))})}).flatten(),this.options)}}),Element.CSS_PROPERTIES=$w('backgroundColor backgroundPosition borderBottomColor borderBottomStyle borderBottomWidth borderLeftColor borderLeftStyle borderLeftWidth borderRightColor borderRightStyle borderRightWidth borderSpacing borderTopColor borderTopStyle borderTopWidth bottom clip color fontSize fontWeight height left letterSpacing lineHeight marginBottom marginLeft marginRight marginTop markerOffset maxHeight maxWidth minHeight minWidth opacity outlineColor outlineOffset outlineWidth paddingBottom paddingLeft paddingRight paddingTop right textIndent top width wordSpacing zIndex'),Element.CSS_LENGTH=/^(([\+\-]?[0-9\.]+)(em|ex|px|in|cm|mm|pt|pc|\%))|0$/,String.__parseStyleElement=document.createElement('div'),String.prototype.parseStyle=function(){var a,b=$H();return Prototype.Browser.WebKit?a=new Element('div',{style:this}).style:(String.__parseStyleElement.innerHTML='<div style="'+this+'"></div>',a=String.__parseStyleElement.childNodes[0].style),Element.CSS_PROPERTIES.each(function(c){a[c]&&b.set(c,a[c])}),Prototype.Browser.IE&&this.include('opacity')&&b.set('opacity',this.match(/opacity:\s*((?:0|1)?(?:\.\d*)?)/)[1]),b},document.defaultView&&document.defaultView.getComputedStyle?Element.getStyles=function(a){var b=document.defaultView.getComputedStyle($(a),null);return Element.CSS_PROPERTIES.inject({},function(a,c){return a[c]=b[c],a})}:Element.getStyles=function(a){a=$(a);var c=a.currentStyle,b;return b=Element.CSS_PROPERTIES.inject({},function(a,b){return a[b]=c[b],a}),b.opacity||(b.opacity=a.getOpacity()),b},Effect.Methods={morph:function(a,b){return a=$(a),new Effect.Morph(a,Object.extend({style:b},arguments[2]||{})),a},visualEffect:function(a,c,d){a=$(a);var b=c.dasherize().camelize(),e=b.charAt(0).toUpperCase()+b.substring(1);return new Effect[e](a,d),a},highlight:function(a,b){return a=$(a),new Effect.Highlight(a,b),a}},$w('fade appear grow shrink fold blindUp blindDown slideUp slideDown pulsate shake puff squish switchOff dropOut').each(function(a){Effect.Methods[a]=function(b,c){return b=$(b),Effect[a.charAt(0).toUpperCase()+a.substring(1)](b,c),b}}),$w('getInlineOpacity forceRerendering setContentZoom collectTextNodes collectTextNodesIgnoreClass getStyles').each(function(a){Effect.Methods[a]=Element[a]}),Element.addMethods(Effect.Methods),dynamicOptionListCount=0,dynamicOptionListObjects=new Array;function initDynamicOptionLists(){for(var h=0,a,i,f,d,e,c,b,j,k,g;h<dynamicOptionListObjects.length;h++){if(a=dynamicOptionListObjects[h],a.formName!=null)a.form=document.forms[a.formName];else if(a.formIndex!=null)a.form=document.forms[a.formIndex];else{i=a.fieldNames[0][0];for(f=0;f<document.forms.length;f++)if(typeof document.forms[f][i]!="undefined"){a.form=document.forms[f];break}if(a.form==null){alert("ERROR: Couldn't find form element "+i+" in any form on the page! Init aborted");return}}for(d=0;d<a.fieldNames.length;d++)for(e=0;e<a.fieldNames[d].length-1;e++){if(c=a.form[a.fieldNames[d][e]],typeof c=="undefined"){alert("Select box named "+a.fieldNames[d][e]+" could not be found in the form. Init aborted");return}if(e==0){if(c.options!=null)for(l=0;l<c.options.length;l++)b=c.options[l],j=a.findMatchingOptionInArray(a.options,b.text,b.value,!1),j!=null&&(k=b.selected,g=new Option(b.text,b.value,b.defaultSelected,b.selected),g.selected=b.selected,g.defaultSelected=b.defaultSelected,g.DOLOption=j,c.options[l]=g,c.options[l].selected=k)}c.onchange==null&&(c.onchange=new Function("dynamicOptionListObjects["+a.index+"].change(this)"))}}resetDynamicOptionLists()}function resetDynamicOptionLists(b){for(var c=0,a,d;c<dynamicOptionListObjects.length;c++)if(a=dynamicOptionListObjects[c],typeof b=="undefined"||b==null||b==a.form)for(d=0;d<a.fieldNames.length;d++)a.change(a.form[a.fieldNames[d][0]],!0)}function DOLOption(a,b,c,d){return this.text=a,this.value=b,this.defaultSelected=c,this.selected=d,this.options=new Array,this}function DynamicOptionList(){if(this.form=null,this.options=new Array,this.longestString=new Array,this.numberOfOptions=new Array,this.currentNode=null,this.currentField=null,this.currentNodeDepth=0,this.fieldNames=new Array,this.formIndex=null,this.formName=null,this.fieldListIndexes=new Object,this.fieldIndexes=new Object,this.selectFirstOption=!0,this.numberOfOptions=new Array,this.longestString=new Array,this.values=new Object,this.forValue=DOL_forValue,this.forText=DOL_forText,this.forField=DOL_forField,this.forX=DOL_forX,this.addOptions=DOL_addOptions,this.addOptionsTextValue=DOL_addOptionsTextValue,this.setDefaultOptions=DOL_setDefaultOptions,this.setValues=DOL_setValues,this.setValue=DOL_setValues,this.setFormIndex=DOL_setFormIndex,this.setFormName=DOL_setFormName,this.printOptions=DOL_printOptions,this.addDependentFields=DOL_addDependentFields,this.change=DOL_change,this.child=DOL_child,this.selectChildOptions=DOL_selectChildOptions,this.populateChild=DOL_populateChild,this.change=DOL_change,this.addNewOptionToList=DOL_addNewOptionToList,this.findMatchingOptionInArray=DOL_findMatchingOptionInArray,arguments.length>0){for(var a=0;a<arguments.length;a++)this.fieldListIndexes[arguments[a].toString()]=this.fieldNames.length,this.fieldIndexes[arguments[a].toString()]=a;this.fieldNames[this.fieldNames.length]=arguments}this.index=window.dynamicOptionListCount++,window.dynamicOptionListObjects[this.index]=this}function DOL_findMatchingOptionInArray(c,g,f,h){var b,d,e,a;if(c==null||typeof c=="undefined")return null;b=null,d=null;for(e=0;e<c.length;e++){if(a=c[e],a.value==f&&a.text==g)return a;h||(b==null&&f!=null&&a.value==f&&(b=a),d==null&&g!=null&&a.text==g&&(d=a))}return b!=null?b:d}function DOL_forX(c,d){var b,a;return this.currentNode==null&&(this.currentNodeDepth=0),b=this.currentNode==null?this:this.currentNode,a=this.findMatchingOptionInArray(b.options,d=="text"?c:null,d=="value"?c:null,!1),a==null&&(a=new DOLOption(null,null,!1,!1),a[d]=c,b.options[b.options.length]=a),this.currentNode=a,this.currentNodeDepth++,this}function DOL_forValue(a){return this.forX(a,"value")}function DOL_forText(a){return this.forX(a,"text")}function DOL_forField(a){return this.currentField=a,this}function DOL_addNewOptionToList(a,d,e,f){var c=new DOLOption(d,e,f,!1),b;a==null&&(a=new Array);for(b=0;b<a.length;b++)if(a[b].text==c.text&&a[b].value==c.value)return c.selected&&(a[b].selected=!0),c.defaultSelected&&(a[b].defaultSelected=!0),a;a[a.length]=c}function DOL_addOptions(){var b,a;this.currentNode==null&&(this.currentNode=this),this.currentNode.options==null&&(this.currentNode.options=new Array);for(b=0;b<arguments.length;b++)a=arguments[b],this.addNewOptionToList(this.currentNode.options,a,a,!1),typeof this.numberOfOptions[this.currentNodeDepth]=="undefined"&&(this.numberOfOptions[this.currentNodeDepth]=0),this.currentNode.options.length>this.numberOfOptions[this.currentNodeDepth]&&(this.numberOfOptions[this.currentNodeDepth]=this.currentNode.options.length),(typeof this.longestString[this.currentNodeDepth]=="undefined"||a.length>this.longestString[this.currentNodeDepth].length)&&(this.longestString[this.currentNodeDepth]=a);this.currentNode=null,this.currentNodeDepth=0}function DOL_addOptionsTextValue(){var a,b,c;this.currentNode==null&&(this.currentNode=this),this.currentNode.options==null&&(this.currentNode.options=new Array);for(a=0;a<arguments.length;a++)b=arguments[a++],c=arguments[a],this.addNewOptionToList(this.currentNode.options,b,c,!1),typeof this.numberOfOptions[this.currentNodeDepth]=="undefined"&&(this.numberOfOptions[this.currentNodeDepth]=0),this.currentNode.options.length>this.numberOfOptions[this.currentNodeDepth]&&(this.numberOfOptions[this.currentNodeDepth]=this.currentNode.options.length),(typeof this.longestString[this.currentNodeDepth]=="undefined"||b.length>this.longestString[this.currentNodeDepth].length)&&(this.longestString[this.currentNodeDepth]=b);this.currentNode=null,this.currentNodeDepth=0}function DOL_child(a){var b=this.fieldListIndexes[a.name],c=this.fieldIndexes[a.name];return c<this.fieldNames[b].length-1?this.form[this.fieldNames[b][c+1]]:null}function DOL_setDefaultOptions(){var a,b;this.currentNode==null&&(this.currentNode=this);for(a=0;a<arguments.length;a++)b=this.findMatchingOptionInArray(this.currentNode.options,null,arguments[a],!1),b!=null&&(b.defaultSelected=!0);this.currentNode=null}function DOL_setValues(){if(this.currentField==null){alert("Can't call setValues() without using forField() first!");return}typeof this.values[this.currentField]=="undefined"&&(this.values[this.currentField]=new Object);for(var a=0;a<arguments.length;a++)this.values[this.currentField][arguments[a]]=!0;this.currentField=null}function DOL_setFormIndex(a){this.formIndex=a}function DOL_setFormName(a){this.formName=a}function DOL_printOptions(d){var b,c,a;if(navigator.appName=='Netscape'&&parseInt(navigator.appVersion)<=4){if(b=this.fieldIndexes[d],c="",typeof this.numberOfOptions[b]!="undefined")for(a=0;a<this.numberOfOptions[b];a++)c+="<OPTION>";if(c+="<OPTION>",typeof this.longestString[b]!="undefined")for(a=0;a<this.longestString[b].length;a++)c+="_";document.writeln(c)}}function DOL_addDependentFields(){for(var a=0;a<arguments.length;a++)this.fieldListIndexes[arguments[a].toString()]=this.fieldNames.length,this.fieldIndexes[arguments[a].toString()]=a;this.fieldNames[this.fieldNames.length]=arguments}function DOL_change(c,d){var k,l,a,i,g,b,e,f,h,j;if((d==null||typeof d=="undefined")&&(d=!1),k=this.fieldListIndexes[c.name],l=this.fieldIndexes[c.name],a=this.child(c),a==null)return;if(c.type=="select-one")a.options!=null&&(a.options.length=0),c.options!=null&&c.options.length>0&&c.selectedIndex>=0&&(i=c.options[c.selectedIndex],this.populateChild(i.DOLOption,a,d),this.selectChildOptions(a,d));else if(c.type=="select-multiple"){if(g=new Array,!d)for(b=0;b<a.options.length;b++)e=a.options[b],e.selected&&this.addNewOptionToList(g,e.text,e.value,e.defaultSelected);if(a.options.length=0,c.options!=null){f=c.options;for(b=0;b<f.length;b++)f[b].selected&&this.populateChild(f[b].DOLOption,a,d);if(h=!1,!d)for(b=0;b<a.options.length;b++)j=this.findMatchingOptionInArray(g,a.options[b].text,a.options[b].value,!0),j!=null&&(a.options[b].selected=!0,h=!0);h||this.selectChildOptions(a,d)}}this.change(a,d)}function DOL_populateChild(d,a,j){var f,b,h,i,e,g,c;if(d!=null&&d.options!=null)for(f=0;f<d.options.length;f++){b=d.options[f],a.options==null&&(a.options=new Array),h=!1,i=!1;for(e=0;e<a.options.length;e++)if(g=a.options[e],g.text==b.text&&g.value==b.value){h=!0;break}h||(c=new Option(b.text,b.value,!1,!1),c.selected=!1,c.defaultSelected=!1,c.DOLOption=b,a.options[a.options.length]=c)}}function DOL_selectChildOptions(a,h){var d=this.values[a.name],f=!1,c,g,e,b;if(h&&d!=null&&typeof d!="undefined")for(c=0;c<a.options.length;c++)if(g=a.options[c].value,g!=null&&d[g]!=null&&typeof d[g]!="undefined"){f=!0;break}e=!1;for(c=0;c<a.options.length;c++)b=a.options[c],f&&b.value!=null&&d[b.value]!=null&&typeof d[b.value]!="undefined"?(b.selected=!0,e=!0):!f&&b.DOLOption!=null&&b.DOLOption.defaultSelected&&(b.selected=!0,e=!0);this.selectFirstOption&&!e&&a.options.length>0?a.options[0].selected=!0:!e&&a.type=="select-one"&&(a.selectedIndex=-1)}