<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_firewall_request_uri extends WPH_module_component
        {
            function get_component_title()
                {
                    return "Request URI";
                }
                                    
            function get_module_component_settings()
                {
                                                                    
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  'firewall_request_uri',
                                                                                                                          
                                                                    'input_type'    =>  'radio',
                                                       
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    
                                                                    'processing_order'  =>  100
                                                                    
                                                                    );
                                                                    
                    
                                                                    
                    return $this->component_settings;   
                }
                
            
            /**
            * Add the descriptin options to the component
            * 
            * @param mixed $component_settings
            */
            function set_module_components_description( $component_settings )
                {
                    
                    
                    foreach ( $component_settings   as  $component_key  =>  $component_setting )
                        {
                            if ( ! isset ( $component_setting['id'] ) )
                                continue;
                            
                            switch ( $component_setting['id'] )
                                {
                                    case 'firewall_request_uri' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                    'label'         =>  __('Request URI Rules',    'wp-hide-security-enhancer'),
                                                                                                                                    'description'   =>  __('Add Firewall rules for Request URI.', 'wp-hide-security-enhancer'),
                                                                                                                                    
                                                                                                                                    'help'          =>  array(
                                                                                                                                                                'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Request URI Rules',    'wp-hide-security-enhancer'),
                                                                                                                                                                'description'               =>  __("The Request-URI is a Uniform Resource Identifier and identifies the resource upon which to apply the request. ",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                "<br /><br />" . __(" Typical URI is as follows:",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                "<br /><code>--domain--/over/there/</code>" .
                                                                                                                                                                                      
                                                                                                                                                                                                "<br /><br />"  . __("The firewall protects against the following type of attacks:",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                "<ul><li>" . __("HTTP Response Splitting",    'wp-hide-security-enhancer') . "</li>" .
                                                                                                                                                                                                    "<li>" .__("XSS) Cross-Site Scripting",    'wp-hide-security-enhancer') ."</li>" . 
                                                                                                                                                                                                    "<li>" .__("Cache Poisoning",    'wp-hide-security-enhancer') ."</li>" .
                                                                                                                                                                                                    "<li>" .__("Dual-Header Exploits",    'wp-hide-security-enhancer') ."</li>" .
                                                                                                                                                                                                    "<li>" .__("SQL/PHP/Code Injection",    'wp-hide-security-enhancer') ."</li>" .
                                                                                                                                                                                                    "<li>" .__("File Injection/Inclusion",    'wp-hide-security-enhancer') ."</li>" .
                                                                                                                                                                                                    "<li>" .__("Null Byte Injection",    'wp-hide-security-enhancer') ."</li>" .
                                                                                                                                                                                                    "<li>" .__("WordPress exploits such as revslider, timthumb, fckeditor, et al",    'wp-hide-security-enhancer') ."</li>" .
                                                                                                                                                                                                    "<li>" .__("Exploits such as c99shell, phpshell, remoteview, site copier, et al",    'wp-hide-security-enhancer') ."</li>" .
                                                                                                                                                                                                    "<li>" .__("PHP information leakage",    'wp-hide-security-enhancer') . "</li></ul>" .
                                                                                                                                                                                                    "<br /><span class='important'>" . __('After activating the firewall, test everything thoroughly, to ensure none of the rules block the site functionality.', 'wp-hide-security-enhancer') ."</span>",
                                                                                                                                                                'option_documentation_url'  =>  'https://wp-hide.com/documentation/how-wp-hide-pro-firewall-protects-your-site/'
                                                                                                                                                                ),

                                                                                                                                    'options'       =>  array(
                                                                                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                                                                                ),
                                                                                                                                ) );
                                                                break;
            
                                }
                                
                            $component_settings[ $component_key ]   =   $component_setting;
                        }
                    
                    return $component_settings;
                    
                }
            
                
                
            function _callback_saved_firewall_request_uri ( $saved_field_data )
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
       
                    if($this->wph->server_htaccess_config   === TRUE)                               
                        {
                            $processing_response['rewrite'] = <<<EOT
                            
# 8G FIREWALL v1.3 20240222
# https://perishablepress.com/8g-firewall/
  
# 8G:[REQUEST URI]
<IfModule mod_rewrite.c>
        
    RewriteCond %{REQUEST_URI} (,,,) [NC,OR]
    RewriteCond %{REQUEST_URI} (-------) [NC,OR]
    RewriteCond %{REQUEST_URI} (\^|`|<|>|\\\\|\|) [NC,OR]
    RewriteCond %{REQUEST_URI} ([a-z0-9]{2000,}) [NC,OR]
    RewriteCond %{REQUEST_URI} (=?\\\\(\'|%27)/?)(\.) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(\*|\"|\'|\.|,|&|&amp;?)/?$ [NC,OR]
    RewriteCond %{REQUEST_URI} (\.)(php)(\()?([0-9]+)(\))?(/)?$ [NC,OR]
    RewriteCond %{REQUEST_URI} /((.*)header:|(.*)set-cookie:(.*)=) [NC,OR]
    RewriteCond %{REQUEST_URI} (\.(s?ftp-?)config|(s?ftp-?)config\.) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(f?ckfinder|fck/|f?ckeditor|fullclick) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)((force-)?download|framework/main)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (\{0\}|\"?0\"?=\"?0|\(/\(|\.\.\.|\+\+\+|\\\\\\") [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(vbull(etin)?|boards|vbforum|vbweb|webvb)(/)? [NC,OR]
    RewriteCond %{REQUEST_URI} (\.|20)(get|the)(_)(permalink|posts_page_url)(\() [NC,OR]
    RewriteCond %{REQUEST_URI} (///|\?\?|/&&|/\*(.*)\*/|/:/|\\\\\\\\|0x00|%00|%0d%0a) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(cgi_?)?alfa(_?cgiapi|_?data|_?v[0-9]+)?(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)((boot)?_?admin(er|istrator|s)(_events)?)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(/) [NC,OR]
    RewriteCond %{REQUEST_URI} (archive|backup|db|master|sql|wp|www|wwwroot)\.(gz|zip) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(\.?mad|alpha|c99|php|web)?sh(3|e)ll([0-9]+|\w)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(admin-?|file-?)(upload)(bg|_?file|ify|svu|ye)?(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(etc|var)(/)(hidden|secret|shadow|ninja|passwd|tmp)(/)?$ [NC,OR]
    RewriteCond %{REQUEST_URI} (s)?(ftp|http|inurl|php)(s)?(:(/|%2f|%u2215)(/|%2f|%u2215)) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(=|\\$&?|&?(pws|rk)=0|_mm|_vti_|cgi(\.|-)?|(=|/|;|,)nt\.) [NC,OR]
    RewriteCond %{REQUEST_URI} (\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(/)?$ [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(bin)(/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(/)?$ [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|ccx|localhost|makefile|pingserver|wwwroot)(/)? [NC,OR]
    RewriteCond %{REQUEST_URI} ^(/)(123|backup|bak|beta|bkp|default|demo|dev(new|old)?|home|new-?site|null|old|old_files|old1)(/)?$ [NC,OR]
    RewriteCond %{REQUEST_URI} (/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:) [NC,OR]
    RewriteCond %{REQUEST_URI} ^(/)(old-?site(back)?|old(web)?site(here)?|sites?|staging|undefined|wordpress([0-9]+)|wordpress-old)(/)?$ [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(filemanager|htdocs|httpdocs|https?|login|mailman|mailto|msoffice|undefined|usage|var|vhosts|webmaster|www)(/) [NC,OR]
    RewriteCond %{REQUEST_URI} (\(null\)|\{\\\$itemURL\}|cast\(0x|echo(.*)kae|etc/passwd|eval\(|null(.*)null|open_basedir|self/environ|\+union\+all\+select) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(db-?|j-?|my(sql)?-?|setup-?|web-?|wp-?)?(admin-?)?(setup-?)?(conf\b|conf(ig)?)(uration)?(\.?bak|\.inc)?(\.inc|\.old|\.php|\.txt) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)((.*)crlf-?injection|(.*)xss-?protection|__(inc|jsc)|administrator|author-panel|cgi-bin|database|downloader|(db|mysql)-?admin)(/) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(haders|head|hello|helpear|incahe|includes?|indo(sec)?|infos?|install|ioptimizes?|jmail|js|king|kiss|kodox|kro|legion|libsoft)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(awstats|document_root|dologin\.action|error.log|extension/ext|htaccess\.|lib/php|listinfo|phpunit/php|remoteview|server/php|www\.root\.) [NC,OR]
    RewriteCond %{REQUEST_URI} (base64_(en|de)code|benchmark|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]
    RewriteCond %{REQUEST_URI} (posix_(kill|mkfifo|setpgid|setsid|setuid)|(child|proc)_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\(|%28)(.*)(\)|%29) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)((c99|php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|%2e|\(|%28) [NC,OR]
    RewriteCond %{REQUEST_URI} /((wp-)((201\d|202\d|[0-9]{2})|ad|admin(fx|rss|setup)|booking|confirm|crons|data|file|mail|one|plugins?|readindex|reset|setups?|story))(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(^$|-|\!|\w|\.(.*)|100|123|([^iI])?ndex|index\.php/index|3xp|777|7yn|90sec|99|active|aill|ajs\.delivery|al277|alexuse?|ali|allwrite)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(analyser|apache|apikey|apismtp|authenticat(e|ing)|autoload_classmap|backup(_index)?|bakup|bkht|black|bogel|bookmark|bypass|cachee?)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(clean|cm(d|s)|con|connector\.minimal|contexmini|contral|curl(test)?|data(base)?|db|db-cache|db-safe-mode|defau11|defau1t|dompdf|dst)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(elements|emails?|error.log|ecscache|edit-form|eval-stdin|export|evil|fbrrchive|filemga|filenetworks?|f0x|gank(\.php)?|gass|gel|guide)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(logo_img|lufix|mage|marg|mass|mide|moon|mssqli|mybak|myshe|mysql|mytag_js?|nasgor|newfile|news|nf_?tracking|nginx|ngoi|ohayo|old-?index)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(olux|owl|pekok|petx|php-?info|phpping|popup-pomo|priv|r3x|radio|rahma|randominit|readindex|readmy|reads|repair-?bak|robot(s\.txt)?|root)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(router|savepng|semayan|shell|shootme|sky|socket(c|i|iasrgasf)ontrol|sql(bak|_?dump)?|support|sym403|sys|system_log|test|tmp-?(uploads)?)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)(traffic-advice|u2p|udd|ukauka|up__uzegp|up14|upa?|upxx?|vega|vip|vu(ln)?(\w)?|webroot|weki|wikindex|wordpress|wp_logns?|wp_wrong_datlib)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (/)((wp-?)?install(ation)?|wp(3|4|5|6)|wpfootes|wpzip|ws0|wsdl|wso(\w)?|www|(uploads|wp-admin)?xleet(-shell)?|xmlsrpc|xup|xxu|xxx|zibi|zipy)(\.php) [NC,OR]
    RewriteCond %{REQUEST_URI} (bkv74|cachedsimilar|core-stab|crgrvnkb|ctivrc|deadcode|deathshop|dkiz|e7xue|eqxafaj90zir|exploits|ffmkpcal|filellli7|(fox|sid)wso|gel4y|goog1es|gvqqpinc) [NC,OR]
    RewriteCond %{REQUEST_URI} (@md5|00.temp00|0byte|0d4y|0day|0xor|wso1337|1h6j5|3xp|40dd1d|4price|70bex?|a57bze893|abbrevsprl|abruzi|adminer|aqbmkwwx|archivarix|backdoor|beez5|bgvzc29) [NC,OR]
    RewriteCond %{REQUEST_URI} (handler_to_code|hax(0|o)r|hmei7|hnap1|home_url=|ibqyiove|icxbsx|indoxploi|jahat|jijle3|kcrew|keywordspy|laobiao|lock360|longdog|marijuan|mod_(aratic|ariimag)) [NC,OR]
    RewriteCond %{REQUEST_URI} (mobiquo|muiebl|nessus|osbxamip|phpunit|priv8|qcmpecgy|r3vn330|racrew|raiz0|reportserver|r00t|respectmus|rom2823|roseleif|sh3ll|site((.){0,2})copier|sqlpatch|sux0r) [NC,OR]
    RewriteCond %{REQUEST_URI} (sym403|telerik|uddatasql|utchiha|visualfrontend|w0rm|wangdafa|wpyii2|wsoyanzo|x5cv|xattack|xbaner|xertive|xiaolei|xltavrat|xorz|xsamxad|xsvip|xxxs?s?|zabbix|zebda) [NC,OR]
    RewriteCond %{REQUEST_URI} (\.)(7z|ab4|ace|afm|alfa|as(h|m)x?|aspx?|aws|axd|bash|ba?k?|bat|bin|bz2|cfg|cfml?|cgi|cms|conf\b|config|ctl|dat|db|dist|dll|eml|eng(ine)?|env|et2|exe|fec|fla|git(ignore)?)$ [NC,OR]
    RewriteCond %{REQUEST_URI} (\.)(hg|idea|inc|index|ini|inv|jar|jspa?|lib|local|log|lqd|make|mbf|mdb|mmw|mny|mod(ule)?|msi|old|one|orig|out|passwd|pdb|php\.(php|suspect(ed)?)|php([^\/])|phtml?|pl|profiles?)$ [NC,OR]
    RewriteCond %{REQUEST_URI} (\.)(psd|pst|ptdb|production|pwd|py|qbb|qdf|rar|rdf|remote|save|sdb|sql|sh|soa|svn|swf|swl|swo|swp|stx|tar|tax|tgz?|theme|tls|tmb|tmd|wok|wow|xsd|xtmpl|xz|ya?ml|za|zlib)$ [NC]

    RewriteRule .* - [F,L]
    
</IfModule>
EOT;
                        }
                            
                    
                    if($this->wph->server_web_config   === TRUE)
                        {
                            //Not implemented
                        }
                    
                    if($this->wph->server_nginx_config   === TRUE)           
                        {
                            $rewrite        =   array();
                            $rewrite_list   =   array();
                            $rewrite_rules  =   array();
                                                           
                            if( ! is_multisite() )
                                $rewrite_list['blog_id'] =   '1';
                                else
                                $rewrite_list['blog_id'] =   'network';
                                
                            $rewrite_list['type']        =   'firewall';
                            $rewrite_list['description'] =   'Firewall User Agent';
                                      
                            $rewrite_data   =   <<<'EOT'
# 7G FIREWALL - NGINX v1.6
# @ https://perishablepress.com/7g-firewall-nginx/
map $request_uri $bad_request_7g {
    
    default 0;
    
    "~*(\^|`|<|>|\\|\|)" 1;
    "~*([a-z0-9]{2000,})" 2;
    "~*(=?\\\(\'|%27)/?)(\.)" 3;
    "~*(/)(\*|\"|\'|\.|,|&|&amp;?)/?$" 4;
    "~*(\.)(php)(\()?([0-9]+)(\))?(/)?$" 5;
    "~*(/)(vbulletin|boards|vbforum)(/)?" 6;
    "~*(/)((.*)header:|(.*)set-cookie:(.*)=)" 7;
    "~*(/)(ckfinder|fck|fckeditor|fullclick)" 8;
    "~*(\.(s?ftp-?)config|(s?ftp-?)config\.)" 9;
    "~*(\{0\}|\"?0\"?=\"?0|\(/\(|\.\.\.|\+\+\+|\\\")" 10;
    "~*(thumbs?(_editor|open)?|tim(thumbs?)?)(\.php)" 11;
    "~*(\.|20)(get|the)(_)(permalink|posts_page_url)(\()" 12;
    "~*(///|\?\?|/&&|/\*(.*)\*/|/:/|\\\\|0x00|%00|%0d%0a)" 13;
    "~*(/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(/)" 14;
    "~*(/)(etc|var)(/)(hidden|secret|shadow|ninja|passwd|tmp)(/)?$" 15;
    "~*(s)?(ftp|http|inurl|php)(s)?(:(/|%2f|%u2215)(/|%2f|%u2215))" 16;
    "~*(/)(=|\$&?|&?(pws|rk)=0|_mm|_vti_|cgi(\.|-)?|(=|/|;|,)nt\.)" 17;
    "~*(\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(/)?$" 18;
    "~*(/)(bin)(/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(/)?$" 19;
    "~*(/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|localhost|makefile|pingserver|wwwroot)(/)?" 20;
    "~*(\(null\)|\{\$itemURL\}|cAsT\(0x|echo(.*)kae|etc/passwd|eval\(|self/environ|\+union\+all\+select)" 21;
    "~*(/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:)" 22;
    "~*(/)(awstats|(c99|php|web)shell|document_root|error_log|listinfo|muieblack|remoteview|site((.){0,2})copier|sqlpatch|sux0r)" 23;
    "~*(/)((php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|\()" 24;
    "~*(/)(author-panel|class|database|(db|mysql)-?admin|filemanager|htdocs|httpdocs|https?|mailman|mailto|msoffice|_?php-my-admin(.*)|tmp|undefined|usage|var|vhosts|webmaster|www)(/)" 25;
    "~*(base64_(en|de)code|benchmark|child_terminate|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo|posix_(kill|mkfifo|setpgid|setsid|setuid)|proc_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\()(.*)(\))" 26;
    "~*(/)(^$|00.temp00|0day|3index|3xp|70bex?|admin_events|bkht|(php|web)?shell|c99|config(\.)?bak|curltest|db|dompdf|filenetworks|hmei7|index\.php/index\.php/index|jahat|kcrew|keywordspy|libsoft|marg|mobiquo|mysql|nessus|php-?info|racrew|sql|vuln|(web-?|wp-)?(conf\b|config(uration)?)|xertive)(\.php)" 27;
    "~*(\.)(7z|ab4|ace|afm|ashx|aspx?|bash|ba?k?|bin|bz2|cfg|cfml?|cgi|conf\b|config|ctl|dat|db|dist|dll|eml|engine|env|et2|exe|fec|fla|git|hg|inc|ini|inv|jsp|log|lqd|make|mbf|mdb|mmw|mny|module|old|one|orig|out|passwd|pdb|phtml|pl|profile|psd|pst|ptdb|pwd|py|qbb|qdf|rar|rdf|save|sdb|sql|sh|soa|svn|swf|swl|swo|swp|stx|tar|tax|tgz|theme|tls|tmd|wow|xtmpl|ya?ml|zlib)$" 28;
    
}  
EOT;
                                  
                                  
                            $rewrite_rules[]            =   $rewrite_data;
                            $rewrite_list['data']       =   $rewrite_rules;
                            $rewrite[]  =   $rewrite_list;
                            
                            
                            $rewrite_list   =   array();
                            $rewrite_rules  =   array();
                                                           
                            if( ! is_multisite() )
                                $rewrite_list['blog_id'] =   '1';
                                else
                                $rewrite_list['blog_id'] =   'network';
                                
                            $rewrite_list['type']        =   'firewall_conditionals';
                            $rewrite_list['description'] =   'Firewall User Agent conditionals';
                                      
                            $rewrite_data   =   '


if ($bad_request_7g) {

    set $7g_reason "${7g_reason}:bad_request_${bad_request_7g}:"; 
    set $7g_drop_bad_request 1;

}
if ($7g_drop_bad_request) {

    set $args "${7g_reason}";
    set $7g_drop 1;

}
                            
                            ';
                                  
                                  
                            $rewrite_rules[]            =   $rewrite_data;
                            $rewrite_list['data']       =   $rewrite_rules;
                            
                            
                            
                            
                            
                            
                            $rewrite[]  =   $rewrite_list; 
                            
                            $processing_response['rewrite'] =   $rewrite;
                        }
                                
                    return  $processing_response;   
                }
 

        }
?>