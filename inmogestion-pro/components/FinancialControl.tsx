
import React, { useMemo, useState } from 'react';
import { Lot, Project, Client, LotStatus } from '../types';
import { DollarSign, ArrowUpRight, ArrowDownRight, Search, Download, Filter, Building2, Calendar, Clock } from 'lucide-react';

interface FinancialControlProps {
  lots: Lot[];
  projects: Project[];
  clients: Client[];
}

const FinancialControl: React.FC<FinancialControlProps> = ({ lots, projects, clients }) => {
  const [projectFilter, setProjectFilter] = useState<string>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  const filteredSoldLots = useMemo(() => {
    let result = lots.filter(l => l.status !== LotStatus.AVAILABLE);
    
    if (projectFilter !== 'all') {
      result = result.filter(l => l.projectId === projectFilter);
    }

    if (startDate) {
      result = result.filter(l => l.contractDate && l.contractDate >= startDate);
    }

    if (endDate) {
      result = result.filter(l => l.contractDate && l.contractDate <= endDate);
    }

    if (searchTerm) {
      const lowerSearch = searchTerm.toLowerCase();
      result = result.filter(l => {
        const client = clients.find(c => c.id === l.clientId);
        return (
          client?.name.toLowerCase().includes(lowerSearch) ||
          client?.dni.includes(lowerSearch) ||
          l.id.toLowerCase().includes(lowerSearch)
        );
      });
    }

    return result;
  }, [lots, projectFilter, searchTerm, clients, startDate, endDate]);
  
  const totalValue = useMemo(() => filteredSoldLots.reduce((acc, l) => acc + l.price, 0), [filteredSoldLots]);
  const totalCollected = useMemo(() => filteredSoldLots.reduce((acc, l) => acc + (l.advance || 0), 0), [filteredSoldLots]);
  const totalPending = totalValue - totalCollected;

  return (
    <div className="space-y-8">
      {/* Financial Header & Filter */}
      <div className="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
          <div>
            <h2 className="text-xl font-black text-slate-800">Control de Caja y Cobranzas</h2>
            <p className="text-sm text-slate-500 font-medium">Seguimiento de ingresos por lotes reservados y transferidos.</p>
          </div>
          <button className="flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all shadow-lg shadow-slate-200">
            <Download className="w-4 h-4" />
            EXPORTAR REPORTE
          </button>
        </div>
        
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="relative">
            <Filter className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <select 
              value={projectFilter}
              onChange={(e) => setProjectFilter(e.target.value)}
              className="pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500 transition-all appearance-none w-full cursor-pointer"
            >
              <option value="all">TODOS LOS PROYECTOS</option>
              {projects.map(p => (
                <option key={p.id} value={p.id}>{p.name.toUpperCase()}</option>
              ))}
            </select>
          </div>
          
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
            <input 
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500 transition-all w-full"
              placeholder="Fecha Inicio"
            />
          </div>

          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
            <input 
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500 transition-all w-full"
              placeholder="Fecha Fin"
            />
          </div>

          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input 
              type="text" 
              placeholder="Buscar cliente..."
              className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-l-blue-500 border border-slate-200">
          <div className="flex items-center justify-between mb-4">
            <div className="bg-blue-50 p-2 rounded-lg">
              <Building2 className="w-6 h-6 text-blue-500" />
            </div>
            <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Portafolio</span>
          </div>
          <p className="text-3xl font-black text-slate-800">S/ {totalValue.toLocaleString()}</p>
          <div className="mt-2 flex items-center gap-1 text-xs font-bold text-blue-600">
            <span>{filteredSoldLots.length} Ventas registradas</span>
          </div>
        </div>

        <div className="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-l-emerald-500 border border-slate-200">
          <div className="flex items-center justify-between mb-4">
            <div className="bg-emerald-50 p-2 rounded-lg">
              <DollarSign className="w-6 h-6 text-emerald-500" />
            </div>
            <div className="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
              Cobrado
            </div>
          </div>
          <p className="text-3xl font-black text-emerald-600">S/ {totalCollected.toLocaleString()}</p>
          <div className="mt-2 flex items-center gap-1 text-xs font-bold text-emerald-500">
            <ArrowUpRight className="w-3 h-3" />
            <span>{Math.round((totalCollected / (totalValue || 1)) * 100)}% de avance</span>
          </div>
        </div>

        <div className="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-l-amber-500 border border-slate-200">
          <div className="flex items-center justify-between mb-4">
            <div className="bg-amber-50 p-2 rounded-lg">
              <ClockIcon className="w-6 h-6 text-amber-500" />
            </div>
            <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Cuentas por Cobrar</span>
          </div>
          <p className="text-3xl font-black text-amber-600">S/ {totalPending.toLocaleString()}</p>
          <div className="mt-2 flex items-center gap-1 text-xs font-bold text-amber-500">
            <ArrowDownRight className="w-3 h-3" />
            <span>Pendiente de liquidación</span>
          </div>
        </div>
      </div>

      {/* Transactions Table Section */}
      <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="p-6 border-b border-slate-100 flex items-center gap-2">
          <div className="w-2 h-6 bg-emerald-500 rounded-full"></div>
          <h3 className="text-lg font-black text-slate-800 uppercase tracking-tight">Detalle de Ventas</h3>
        </div>

        <div className="overflow-x-auto">
          {filteredSoldLots.length > 0 ? (
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50 border-b border-slate-100">
                  <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha / Lote</th>
                  <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente / Documento</th>
                  <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Proyecto</th>
                  <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Monto Total</th>
                  <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Abonado</th>
                  <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Saldo</th>
                  <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">% Rec.</th>
                  <th className="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Acción</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredSoldLots.map(lot => {
                  const client = clients.find(c => c.id === lot.clientId);
                  const project = projects.find(p => p.id === lot.projectId);
                  const percent = Math.round(((lot.advance || 0) / lot.price) * 100);
                  
                  return (
                    <tr key={lot.id} className="hover:bg-slate-50 transition-colors group">
                      <td className="px-6 py-5">
                        <p className="text-xs font-black text-slate-800 uppercase leading-none mb-1">{lot.contractDate || '---'}</p>
                        <span className="bg-slate-900 text-white text-[9px] px-2 py-0.5 rounded font-black">MZ {lot.block}-{lot.number}</span>
                      </td>
                      <td className="px-6 py-5">
                        <p className="text-sm font-black text-slate-800 mb-0.5">{client?.name || 'Inversionista Sin Nombre'}</p>
                        <p className="text-[10px] font-bold text-slate-400 uppercase">DNI: {client?.dni || 'XXXXXXXX'}</p>
                      </td>
                      <td className="px-6 py-5">
                        <p className="text-[10px] font-black text-emerald-600 uppercase">{project?.name}</p>
                        <p className="text-[9px] font-bold text-slate-400 uppercase">{project?.location}</p>
                      </td>
                      <td className="px-6 py-5">
                        <span className="text-sm font-black text-slate-800">S/ {lot.price.toLocaleString()}</span>
                      </td>
                      <td className="px-6 py-5">
                        <span className="text-sm font-black text-emerald-600">S/ {(lot.advance || 0).toLocaleString()}</span>
                      </td>
                      <td className="px-6 py-5">
                        <span className="text-sm font-black text-amber-600">S/ {(lot.remainingBalance || 0).toLocaleString()}</span>
                      </td>
                      <td className="px-6 py-5">
                        <div className="flex flex-col gap-1">
                          <span className="text-[10px] font-black text-slate-700">{percent}%</span>
                          <div className="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div 
                              className={`h-full rounded-full transition-all duration-500 ${percent > 70 ? 'bg-emerald-500' : percent > 30 ? 'bg-amber-500' : 'bg-red-500'}`}
                              style={{ width: `${percent}%` }}
                            ></div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-5 text-right">
                        <button className="p-2 hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                          <DollarSign className="w-5 h-5" />
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          ) : (
            <div className="p-20 text-center flex flex-col items-center justify-center">
              <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                <Search className="w-8 h-8" />
              </div>
              <h4 className="text-slate-800 font-black uppercase">Sin resultados</h4>
              <p className="text-slate-400 text-sm">No se encontraron ventas para los criterios seleccionados.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

const ClockIcon = ({ className }: { className?: string }) => (
  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
);

export default FinancialControl;
