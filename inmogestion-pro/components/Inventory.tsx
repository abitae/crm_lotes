
import React, { useState } from 'react';
import { Project, Lot, LotStatus, Client, Advisor } from '../types';
import { Search, Filter, Info, Edit3, UserPlus, FileText, Map as MapIcon, ChevronDown } from 'lucide-react';

interface InventoryProps {
  selectedProject: Project;
  lots: Lot[];
  clients: Client[];
  advisors: Advisor[];
  onUpdateLot: (lot: Lot) => void;
}

const Inventory: React.FC<InventoryProps> = ({ selectedProject, lots, onUpdateLot, clients, advisors }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedLot, setSelectedLot] = useState<Lot | null>(null);

  const getStatusColor = (status: LotStatus) => {
    switch (status) {
      case LotStatus.AVAILABLE: return 'bg-emerald-500 hover:bg-emerald-600';
      case LotStatus.RESERVED: return 'bg-amber-500 hover:bg-amber-600';
      case LotStatus.TRANSFERRED: return 'bg-slate-400 hover:bg-slate-500';
      case LotStatus.QUOTAS: return 'bg-indigo-500 hover:bg-indigo-600';
      default: return 'bg-slate-200';
    }
  };

  const filteredLots = lots.filter(l => 
    l.id.toLowerCase().includes(searchTerm.toLowerCase()) ||
    l.number.toString().includes(searchTerm)
  );

  return (
    <div className="flex flex-col lg:flex-row gap-8 h-full">
      {/* Left side: Grouped Grid */}
      <div className="flex-1 bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col min-w-0">
        <div className="flex flex-col sm:flex-row gap-4 mb-8">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input 
              type="text" 
              placeholder="Buscar por número o ID de lote..."
              className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <div className="flex items-center gap-4 text-xs font-bold uppercase tracking-tight text-slate-400">
            <div className="flex items-center gap-1.5">
              <div className="w-3 h-3 rounded bg-emerald-500"></div>
              <span>Libre</span>
            </div>
            <div className="flex items-center gap-1.5">
              <div className="w-3 h-3 rounded bg-amber-500"></div>
              <span>Reserva</span>
            </div>
            <div className="flex items-center gap-1.5">
              <div className="w-3 h-3 rounded bg-slate-400"></div>
              <span>Vendido</span>
            </div>
          </div>
        </div>

        {/* Organized by Blocks */}
        <div className="flex-1 overflow-y-auto pr-2 space-y-10 custom-scrollbar">
          {selectedProject.totalBlocks.map(block => {
            const blockLots = filteredLots.filter(l => l.block === block);
            if (blockLots.length === 0) return null;

            return (
              <section key={block} className="space-y-4">
                <div className="flex items-center gap-3 border-b border-slate-100 pb-2">
                  <div className="bg-slate-900 text-white text-xs font-black px-3 py-1 rounded-full">
                    MANZANA {block}
                  </div>
                  <span className="text-xs font-medium text-slate-400 uppercase tracking-widest">
                    {blockLots.length} Lotes en total
                  </span>
                </div>
                
                <div className="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-7 lg:grid-cols-9 gap-3">
                  {blockLots.sort((a,b) => a.number - b.number).map(lot => (
                    <button
                      key={lot.id}
                      onClick={() => setSelectedLot(lot)}
                      className={`relative aspect-square rounded-lg flex flex-col items-center justify-center p-1 text-white transition-all transform hover:scale-105 active:scale-95 shadow-sm ${getStatusColor(lot.status)} ${selectedLot?.id === lot.id ? 'ring-4 ring-offset-2 ring-emerald-400 z-10' : ''}`}
                    >
                      <span className="text-lg font-black leading-none">{lot.number}</span>
                      <span className="text-[9px] font-bold opacity-80 uppercase">{lot.area}m²</span>
                      {selectedLot?.id === lot.id && (
                        <div className="absolute -top-1 -right-1 bg-white rounded-full p-0.5 shadow-md">
                          <Info className="w-3 h-3 text-emerald-600" />
                        </div>
                      )}
                    </button>
                  ))}
                </div>
              </section>
            );
          })}
        </div>
      </div>

      {/* Right side: Selection Detail */}
      <div className="w-full lg:w-96 shrink-0">
        {selectedLot ? (
          <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden sticky top-8">
            <div className={`h-2 ${getStatusColor(selectedLot.status)}`}></div>
            <div className="p-6">
              <div className="mb-6">
                <div className="flex justify-between items-start mb-1">
                  <h3 className="text-2xl font-black text-slate-800 leading-tight">Lote {selectedLot.number}</h3>
                  <span className={`px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider ${
                    selectedLot.status === LotStatus.AVAILABLE ? 'bg-emerald-100 text-emerald-700' : 
                    selectedLot.status === LotStatus.RESERVED ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'
                  }`}>
                    {selectedLot.status}
                  </span>
                </div>
                <p className="text-sm font-semibold text-slate-400 uppercase tracking-wide">
                  Manzana {selectedLot.block} • {selectedProject.name}
                </p>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-8">
                <div className="bg-slate-50 p-3 rounded-xl border border-slate-100">
                  <p className="text-[10px] uppercase font-bold text-slate-400 mb-1">Precio de Lista</p>
                  <p className="text-lg font-black text-slate-800">S/ {selectedLot.price.toLocaleString()}</p>
                </div>
                <div className="bg-slate-50 p-3 rounded-xl border border-slate-100">
                  <p className="text-[10px] uppercase font-bold text-slate-400 mb-1">Área Terreno</p>
                  <p className="text-lg font-black text-slate-800">{selectedLot.area} m²</p>
                </div>
              </div>

              {selectedLot.status !== LotStatus.AVAILABLE ? (
                <div className="space-y-4 mb-8">
                  <h4 className="text-xs font-black uppercase text-slate-900 tracking-widest border-b border-slate-100 pb-2 flex items-center gap-2">
                    <UserPlus className="w-4 h-4 text-emerald-500" />
                    Asignación de Lote
                  </h4>
                  <div className="bg-slate-50 p-4 rounded-xl space-y-3">
                    <div>
                      <p className="text-[10px] font-bold text-slate-400 uppercase">Propietario / Interesado</p>
                      <p className="text-sm font-bold text-slate-800">{clients.find(c => c.id === selectedLot.clientId)?.name || 'Sin Asignar'}</p>
                    </div>
                    <div>
                      <p className="text-[10px] font-bold text-slate-400 uppercase">Asesor Comercial</p>
                      <p className="text-sm font-bold text-slate-800">{advisors.find(a => a.id === selectedLot.advisorId)?.name || 'Vendedor Interno'}</p>
                    </div>
                    {selectedLot.advance !== undefined && (
                      <div>
                        <p className="text-[10px] font-bold text-slate-400 uppercase">Adelanto / Reserva</p>
                        <p className="text-sm font-bold text-emerald-600">S/ {selectedLot.advance.toLocaleString()}</p>
                      </div>
                    )}
                  </div>
                </div>
              ) : (
                <div className="bg-emerald-50 p-4 rounded-xl border border-emerald-100 mb-8">
                  <p className="text-emerald-700 text-xs font-medium flex items-start gap-2">
                    <Info className="w-4 h-4 shrink-0 mt-0.5" />
                    Este lote se encuentra disponible para la venta. Puedes iniciar el proceso de reserva o transferencia directa.
                  </p>
                </div>
              )}

              <div className="space-y-3">
                <button className="w-full bg-slate-900 hover:bg-slate-800 text-white font-black py-3.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-slate-200">
                  <Edit3 className="w-4 h-4" />
                  GESTIONAR ESTADO
                </button>
                <button className="w-full bg-white border-2 border-slate-100 hover:border-slate-200 text-slate-600 font-bold py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2">
                  <FileText className="w-4 h-4" />
                  VER PLANOS PDF
                </button>
              </div>
            </div>
          </div>
        ) : (
          <div className="bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl p-10 text-center flex flex-col items-center justify-center h-[500px] sticky top-8">
            <div className="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-6">
              <MapIcon className="w-8 h-8 text-slate-300" />
            </div>
            <h4 className="text-slate-800 font-black mb-2 uppercase tracking-tight">Mapa Interactivo</h4>
            <p className="text-slate-400 text-sm max-w-[200px] mx-auto leading-relaxed">
              Selecciona cualquier lote del plano para visualizar su ficha técnica y financiera.
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

export default Inventory;
