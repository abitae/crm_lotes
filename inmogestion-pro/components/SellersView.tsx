
import React, { useState, useMemo } from 'react';
import { Advisor, Lot, AdvisorLevel, LotStatus } from '../types';
import { UserCheck, Phone, ChevronRight, UserPlus, Search, Target, ShieldCheck } from 'lucide-react';

interface SellersViewProps {
  advisors: Advisor[];
  lots: Lot[];
}

const SellersView: React.FC<SellersViewProps> = ({ advisors, lots }) => {
  const [searchTerm, setSearchTerm] = useState('');

  // Niveles únicos para los filtros y resúmenes
  const availableLevels = useMemo(() => {
    // Explicitly cast to string[] to resolve type inference issues where 'a' and 'b' in sort become 'unknown'
    const levels = [...new Set(advisors.map(a => a.level))] as string[];
    return levels.sort((a, b) => {
      const numA = parseInt(a.replace(/\D/g, '')) || 0;
      const numB = parseInt(b.replace(/\D/g, '')) || 0;
      return numA - numB;
    });
  }, [advisors]);

  const getLevelTheme = (level: string) => {
    const num = parseInt(level.replace(/\D/g, '')) || 1;
    const colors = [
      { bg: 'bg-blue-600', text: 'text-white' },
      { bg: 'bg-emerald-600', text: 'text-white' },
      { bg: 'bg-indigo-600', text: 'text-white' },
      { bg: 'bg-purple-600', text: 'text-white' },
      { bg: 'bg-pink-600', text: 'text-white' },
      { bg: 'bg-amber-600', text: 'text-white' }
    ];
    return colors[(num - 1) % colors.length];
  };

  const filteredAdvisors = advisors.filter(a => 
    a.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    a.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-2xl font-black text-slate-800 uppercase tracking-tight">Estructura Comercial</h2>
          <p className="text-sm text-slate-500 font-medium italic">Gestión de jerarquías ilimitadas y reporte directo.</p>
        </div>
        <button className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-black py-2.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-xl shadow-slate-200 transition-all active:scale-95">
          <UserPlus className="w-5 h-5" />
          NUEVO ASESOR
        </button>
      </div>

      {/* Dynamic Summary Tabs for Levels */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        {availableLevels.map(level => {
          const theme = getLevelTheme(level);
          const count = advisors.filter(a => a.level === level).length;
          return (
            <div key={level} className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center">
              <span className="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">{level}</span>
              <div className={`${theme.bg} ${theme.text} w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm mb-1`}>
                {count}
              </div>
              <span className="text-[10px] font-bold text-slate-400 uppercase">Integrantes</span>
            </div>
          );
        })}
      </div>

      <div className="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
          <div className="relative w-full sm:w-80">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input 
              type="text" 
              placeholder="Buscar por nombre o cargo..."
              className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 transition-all font-medium"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <div className="flex items-center gap-2">
            <ShieldCheck className="w-4 h-4 text-emerald-500" />
            <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Personal Activo: {advisors.length}</span>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/50 border-b border-slate-100">
                <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Nivel / Vendedor</th>
                <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Superior Responsable</th>
                <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Cuota Personal</th>
                <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Ventas</th>
                <th className="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredAdvisors.map(adv => {
                const superior = advisors.find(a => a.id === adv.superiorId);
                const sales = lots.filter(l => l.advisorId === adv.id && l.status !== LotStatus.AVAILABLE);
                const totalSalesAmount = sales.reduce((acc, l) => acc + l.price, 0);
                const theme = getLevelTheme(adv.level);
                
                return (
                  <tr key={adv.id} className="hover:bg-slate-50 transition-colors group">
                    <td className="px-6 py-5">
                      <div className="flex items-center gap-3">
                        <div className={`px-2.5 py-1 rounded-lg text-[10px] font-black shadow-sm ${theme.bg} ${theme.text}`}>
                          {adv.level}
                        </div>
                        <div>
                          <p className="text-sm font-black text-slate-800 leading-none">{adv.name}</p>
                          <p className="text-[9px] font-medium text-slate-400 mt-1 uppercase">{adv.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-5">
                      {superior ? (
                        <div className="flex items-center gap-2">
                          <div className="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                          <p className="text-xs font-bold text-slate-700">{superior.name}</p>
                        </div>
                      ) : (
                        <span className="text-[10px] font-black text-purple-600 uppercase tracking-tighter italic">Alta Gerencia</span>
                      )}
                    </td>
                    <td className="px-6 py-5">
                      <div className="flex items-center gap-2 text-slate-700">
                        <Target className="w-3.5 h-3.5 text-slate-300" />
                        <span className="text-xs font-bold">S/ {adv.personalQuota.toLocaleString()}</span>
                      </div>
                    </td>
                    <td className="px-6 py-5">
                      <div className="flex items-center gap-3">
                        <span className="text-sm font-black text-slate-800">S/ {totalSalesAmount.toLocaleString()}</span>
                        <div className="bg-slate-100 px-2 py-0.5 rounded text-[9px] font-black text-slate-500 uppercase">
                          {sales.length} Lts
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-5 text-right">
                      <button className="p-2 hover:bg-slate-100 text-slate-400 hover:text-slate-900 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                        <ChevronRight className="w-5 h-5" />
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default SellersView;
