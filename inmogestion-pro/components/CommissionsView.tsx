
import React, { useState, useMemo } from 'react';
import { Lot, Advisor, LotStatus, Project } from '../types';
import { Percent, TrendingUp, Users, DollarSign, Settings, Search, CheckCircle2, AlertCircle, ChevronDown, Pyramid, Calendar } from 'lucide-react';

interface CommissionsViewProps {
  lots: Lot[];
  advisors: Advisor[];
  projects: Project[];
}

const CommissionsView: React.FC<CommissionsViewProps> = ({ lots, advisors, projects }) => {
  // Configurable commission rates
  const [directRate, setDirectRate] = useState(7);
  const [pyramidRate, setPyramidRate] = useState(1);
  const [searchTerm, setSearchTerm] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  // Calculate commissions based on transferred lots
  const commissionData = useMemo(() => {
    const data: any[] = [];
    
    lots.filter(l => l.status === LotStatus.TRANSFERRED && l.advisorId).forEach(lot => {
      const seller = advisors.find(a => a.id === lot.advisorId);
      if (!seller) return;

      // Filter by date
      if (startDate && lot.contractDate && lot.contractDate < startDate) return;
      if (endDate && lot.contractDate && lot.contractDate > endDate) return;

      // Direct Commission (7%)
      const directAmount = (lot.price * directRate) / 100;
      data.push({
        id: `comm-direct-${lot.id}`,
        lotId: lot.id,
        lotNumber: `${lot.block}-${lot.number}`,
        advisorName: seller.name,
        advisorLevel: seller.level,
        amount: directAmount,
        percentage: directRate,
        type: 'DIRECTA',
        lotPrice: lot.price,
        status: 'PENDIENTE',
        projectId: lot.projectId,
        date: lot.contractDate
      });

      // Pyramid Commission (1%) for immediate superior
      if (seller.superiorId) {
        const superior = advisors.find(a => a.id === seller.superiorId);
        if (superior) {
          const superiorAmount = (lot.price * pyramidRate) / 100;
          data.push({
            id: `comm-pyramid-${lot.id}`,
            lotId: lot.id,
            lotNumber: `${lot.block}-${lot.number}`,
            advisorName: superior.name,
            advisorLevel: superior.level,
            amount: superiorAmount,
            percentage: pyramidRate,
            type: 'PIRAMIDAL',
            lotPrice: lot.price,
            status: 'PENDIENTE',
            projectId: lot.projectId,
            sourceAdvisor: seller.name,
            date: lot.contractDate
          });
        }
      }
    });

    return data;
  }, [lots, advisors, directRate, pyramidRate, startDate, endDate]);

  const filteredCommissions = commissionData.filter(c => 
    c.advisorName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    c.lotNumber.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const totalCommissions = useMemo(() => commissionData.reduce((acc, c) => acc + c.amount, 0), [commissionData]);
  const totalDirect = useMemo(() => commissionData.filter(c => c.type === 'DIRECTA').reduce((acc, c) => acc + c.amount, 0), [commissionData]);
  const totalPyramid = useMemo(() => commissionData.filter(c => c.type === 'PIRAMIDAL').reduce((acc, c) => acc + c.amount, 0), [commissionData]);

  return (
    <div className="space-y-8 pb-12">
      {/* Header & Settings */}
      <div className="flex flex-col xl:flex-row gap-6">
        <div className="flex-1 bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
              <h2 className="text-2xl font-black text-slate-800 uppercase tracking-tight flex items-center gap-2">
                <Pyramid className="w-6 h-6 text-emerald-500" />
                Gestión de Comisiones
              </h2>
              <p className="text-sm text-slate-500 font-medium">Cálculo piramidal basado en ventas efectivas (Transferidas).</p>
            </div>
            
            {/* Rates Configuration */}
            <div className="flex flex-wrap items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
              <div className="flex items-center gap-3">
                <Settings className="w-4 h-4 text-slate-400" />
                <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Tasas Config:</span>
              </div>
              <div className="flex items-center gap-2">
                <label className="text-[10px] font-bold text-slate-500 uppercase">Vendedor (%)</label>
                <input 
                  type="number" 
                  value={directRate} 
                  onChange={(e) => setDirectRate(Number(e.target.value))}
                  className="w-14 p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-black text-center outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                />
              </div>
              <div className="flex items-center gap-2">
                <label className="text-[10px] font-bold text-slate-500 uppercase">Superior (%)</label>
                <input 
                  type="number" 
                  value={pyramidRate} 
                  onChange={(e) => setPyramidRate(Number(e.target.value))}
                  className="w-14 p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-black text-center outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                />
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-emerald-50 p-6 rounded-2xl border border-emerald-100">
              <div className="flex justify-between items-center mb-2">
                <p className="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Total Comisiones</p>
                <DollarSign className="w-4 h-4 text-emerald-500" />
              </div>
              <p className="text-2xl font-black text-emerald-700">S/ {totalCommissions.toLocaleString()}</p>
              <p className="text-[10px] text-emerald-500 font-bold uppercase mt-1">Acumulado General</p>
            </div>
            
            <div className="bg-blue-50 p-6 rounded-2xl border border-blue-100">
              <div className="flex justify-between items-center mb-2">
                <p className="text-[10px] font-black text-blue-600 uppercase tracking-widest">Directas ({directRate}%)</p>
                <TrendingUp className="w-4 h-4 text-blue-500" />
              </div>
              <p className="text-2xl font-black text-blue-700">S/ {totalDirect.toLocaleString()}</p>
              <p className="text-[10px] text-blue-500 font-bold uppercase mt-1">Venta Directa</p>
            </div>

            <div className="bg-purple-50 p-6 rounded-2xl border border-purple-100">
              <div className="flex justify-between items-center mb-2">
                <p className="text-[10px] font-black text-purple-600 uppercase tracking-widest">Piramidales ({pyramidRate}%)</p>
                <Users className="w-4 h-4 text-purple-500" />
              </div>
              <p className="text-2xl font-black text-purple-700">S/ {totalPyramid.toLocaleString()}</p>
              <p className="text-[10px] text-purple-500 font-bold uppercase mt-1">Venta Indirecta</p>
            </div>
          </div>
        </div>
      </div>

      {/* Commission Table */}
      <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-6 border-b border-slate-100 bg-slate-50/50">
          <div className="flex flex-col lg:flex-row justify-between items-center gap-4">
            <div className="flex items-center gap-3">
              <div className="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
              <h3 className="text-sm font-black text-slate-800 uppercase tracking-tight">Registro de Liquidación</h3>
            </div>
            
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full lg:w-auto">
              <div className="relative">
                <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                <input 
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="pl-10 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-emerald-500 w-full"
                />
              </div>
              <div className="relative">
                <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                <input 
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="pl-10 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-emerald-500 w-full"
                />
              </div>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input 
                  type="text" 
                  placeholder="Buscar vendedor..."
                  className="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/30">
                <th className="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha / Asesor</th>
                <th className="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Monto Lote Vendido</th>
                <th className="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tipo / %</th>
                <th className="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Comisión Generada</th>
                <th className="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {filteredCommissions.map((comm) => (
                <tr key={comm.id} className="hover:bg-slate-50/80 transition-colors group">
                  <td className="px-8 py-5">
                    <div className="flex items-center gap-3">
                      <div className="shrink-0 flex flex-col items-center">
                        <span className="text-[9px] font-black text-slate-400 mb-1 leading-none uppercase">{comm.date}</span>
                        <div className="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center font-black text-[10px] text-white">
                          {comm.advisorName.charAt(0)}
                        </div>
                      </div>
                      <div>
                        <p className="text-sm font-black text-slate-800 uppercase leading-none mb-1">{comm.advisorName}</p>
                        <span className="text-[9px] font-bold text-emerald-600 uppercase tracking-tighter">LOT-{comm.lotNumber} ({comm.advisorLevel})</span>
                      </div>
                    </div>
                  </td>
                  <td className="px-8 py-5">
                    <div className="flex flex-col">
                      <span className="text-sm font-black text-slate-800">S/ {comm.lotPrice.toLocaleString()}</span>
                      <span className="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Precio de Venta</span>
                    </div>
                  </td>
                  <td className="px-8 py-5">
                    <div className="flex flex-col gap-1">
                      <span className={`inline-flex px-2 py-0.5 rounded text-[9px] font-black uppercase w-fit shadow-sm ${
                        comm.type === 'DIRECTA' ? 'bg-blue-100 text-blue-700 border border-blue-200' : 'bg-purple-100 text-purple-700 border border-purple-200'
                      }`}>
                        {comm.type}
                      </span>
                      <span className="text-[10px] font-bold text-slate-500 tracking-widest ml-1">{comm.percentage}%</span>
                    </div>
                    {comm.sourceAdvisor && (
                      <p className="text-[9px] font-bold text-slate-400 italic mt-1 leading-tight">De venta: {comm.sourceAdvisor}</p>
                    )}
                  </td>
                  <td className="px-8 py-5">
                    <div className="flex flex-col">
                      <p className="text-lg font-black text-emerald-600 leading-none">S/ {comm.amount.toLocaleString()}</p>
                      <span className="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Liquidación</span>
                    </div>
                  </td>
                  <td className="px-8 py-5 text-right">
                    <div className="flex items-center justify-end gap-2">
                      <span className="flex items-center gap-1.5 px-2 py-1 bg-amber-50 text-amber-600 border border-amber-100 rounded-lg text-[9px] font-black uppercase tracking-tighter shadow-sm">
                        <AlertCircle className="w-3 h-3" />
                        Pendiente
                      </span>
                      <button className="p-2 hover:bg-emerald-50 text-emerald-600 rounded-lg transition-all opacity-0 group-hover:opacity-100" title="Marcar como pagado">
                        <CheckCircle2 className="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {filteredCommissions.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-24 text-center">
                    <div className="flex flex-col items-center justify-center text-slate-300">
                      <Percent className="w-12 h-12 mb-4 opacity-10" />
                      <p className="text-sm font-black uppercase tracking-widest">No hay registros para este período</p>
                      <p className="text-xs font-medium">Ajusta los filtros o confirma ventas transferidas para generar comisiones.</p>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default CommissionsView;
