<?php
namespace Kijtra\DB;

interface Constant
{
    const VERSION = '0.3.0';

    const NS_BASE = __NAMESPACE__.'\\';

    const CLASS_CONNECTION = __NAMESPACE__.'\\Connection';
    const CLASS_STATEMENT = __NAMESPACE__.'\\Statement';
    const CLASS_HISTORY = __NAMESPACE__.'\\History';

    const CLASS_TABLE = __NAMESPACE__.'\\Container\\Table';
    const CLASS_COLUMN = __NAMESPACE__.'\\Container\\Column';
    const CLASS_COLUMNS = __NAMESPACE__.'\\Container\\Columns';

    const CLASS_FORMATTER = __NAMESPACE__.'\\Component\\Formatter';
    const CLASS_VALIDATOR = __NAMESPACE__.'\\Component\\Validator';

    const PROP_CONN = 'conn';

    const DEFAULT_CHARSET = 'utf8';
    const DEFAULT_HOST = 'localhost';
}
