<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Instrument extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'instruments';
    
    protected $fillable = [
        'file_upload_id',
        'RptDt', 
        'TckrSymb', 
        'Asst', '
        AsstDesc', 
        'SgmtNm', 
        'MktNm', 
        'SctyCtgyNm', 
        'XprtnDt', 
        'XprtnCd', 
        'TradgStartDt', 
        'TradgEndDt', 
        'BaseCd', 
        'ConvsCritNm', 
        'MtrtyDtTrgtPt', 
        'ReqrdConvsInd', 
        'ISIN', 
        'CFICd', 
        'DlvryNtceStartDt', 
        'DlvryNtceEndDt', 
        'OptnTp', 
        'CtrctMltplr', 
        'AsstQtnQty', 
        'AllcnRndLot', 
        'TradgCcy', 
        'DlvryTpNm', 
        'WdrwlDays', 
        'WrkgDays', 
        'ClnrDays', 
        'RlvrBasePricNm', 
        'OpngFutrPosDay', 
        'SdTpCd1', 
        'UndrlygTckrSymb1', 
        'SdTpCd2', 
        'UndrlygTckrSymb2', 
        'PureGoldWght', 
        'ExrcPric', 
        'OptnStyle', 
        'ValTpNm', 
        'PrmUpfrntInd', 
        'OpngPosLmtDt', 
        'DstrbtnId', 
        'PricFctr', 
        'DaysToSttlm', 
        'SrsTpNm', 
        'PrtcnFlg', 
        'AutomtcExrcInd', 
        'SpcfctnCd', 
        'CrpnNm', 
        'CorpActnStartDt', 
        'CtdyTrtmntTpNm',
        'MktCptlstn',
        'CorpGovnLvlNm'
    ];
    

    protected $casts = [
        'RptDt' => 'date',
        'TckrSymb' => 'string',
        'MktNm' => 'string',
        'SctyCtgyNm' => 'string',
        'ISIN' => 'string',
        'CrpnNm' => 'string'
    ];
    
    protected $dates = ['RptDt'];
    
    // Índices para performance
    public static function getIndexes(): array
    {
        return [
            ['RptDt' => -1, 'TckrSymb' => 1],
            ['TckrSymb' => 1],
            ['RptDt' => -1]
        ];
    }
    
    // Relacionamento com o upload
    public function fileUpload()
    {
        return $this->belongsTo(FileUpload::class, 
        'file_upload_id', 'id');
    }
    
    // Formatar data para API
    public function getRptDtAttribute($value)
    {
        return $value ? date('Y-m-d', strtotime($value)) : null;
    }
    
    // Escopo para busca por ticker e data
    public function scopeByTickerAndDate($query, $ticker, $date)
    {
        return $query->where('TckrSymb', $ticker)
                    ->where('RptDt', $date);
    }
    
    // Escopo para busca por data
    public function scopeByDate($query, $date)
    {
        return $query->where('RptDt', $date);
    }
    
    // Escopo para busca por ticker
    public function scopeByTicker($query, $ticker)
    {
        return $query->where('TckrSymb', $ticker);
    }
}