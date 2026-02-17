
import React, { useState } from 'react';
import { Client, Lot, LotStatus, Project } from '../types';
// Fixed missing import: Added Users icon from lucide-react
import { User, Users, Phone, MapPin, ExternalLink, Mail, UserPlus, Search, Download, MoreHorizontal, Eye } from 'lucide-react';

interface ClientsViewProps {
  clients: Client[];
  lots: Lot[];
  projects: Project[];
}

const ClientsView: React.FC<ClientsViewProps> = ({ clients, lots, projects }) => {
  const [searchTerm, setSearchTerm] = useState('');

  const filteredClients = clients.filter(c => 
    c.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    c.dni.includes(searchTerm) ||
    c.phone.includes(searchTerm)
  );

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-2xl font-black text-slate-800">Directorio de Clientes</h2>
          <p className="text-sm text-slate-500 font-medium">Gestión integral de la base de datos de compradores e interesados.</p>
        </div>
        <div className="flex items-center gap-3 w-full sm:w-auto">
          <button className="flex-1 sm:flex-initial bg-white border border-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 hover:bg-slate-50 transition-all">
            <Download className="w-4 h-4" />
            Exportar
          </button>
          <button className="flex-1 sm:flex-initial bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-100 transition-all">
            <UserPlus className="w-5 h-5" />
            Nuevo Cliente
          </button>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <input 
            type="text" 
            placeholder="Buscar por nombre, DNI o celular..."
            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 transition-all font-medium"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
      </div>

      {/* Clients Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/50 border-b border-slate-100">
                <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Nombre Completo / ID</th>
                <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Información de Contacto</th>
                <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Lotes Adquiridos</th>
                <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado Portafolio</th>
                <th className="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredClients.map(client => {
                const clientLots = lots.filter(l => l.clientId === client.id);
                const hasOverdue = false; // Mock logic
                
                return (
                  <tr key={client.id} className="hover:bg-slate-50 transition-colors group">
                    <td className="px-6 py-5">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs shrink-0">
                          {client.name.split(' ').map(n => n[0]).slice(0, 2).join('')}
                        </div>
                        <div>
                          <p className="text-sm font-black text-slate-800 leading-none">{client.name}</p>
                          <p className="text-[10px] font-bold text-slate-400 uppercase mt-1">DNI: {client.dni}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-5">
                      <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs text-slate-600 font-medium">
                          <Phone className="w-3 h-3 text-emerald-500" />
                          <span>{client.phone}</span>
                        </div>
                        <div className="flex items-center gap-2 text-xs text-slate-400 font-medium">
                          <Mail className="w-3 h-3 text-slate-300" />
                          <span>{client.email || 'sin@email.com'}</span>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-5">
                      <div className="flex flex-wrap gap-2">
                        {clientLots.length > 0 ? (
                          clientLots.map(l => (
                            <div key={l.id} className="group/badge relative">
                              <span className={`px-2 py-1 rounded text-[10px] font-black uppercase flex items-center gap-1 shadow-sm ${
                                l.status === LotStatus.TRANSFERRED ? 'bg-slate-900 text-white' : 'bg-amber-100 text-amber-700'
                              }`}>
                                <MapPin className="w-2.5 h-2.5" />
                                {l.block}-{l.number}
                              </span>
                            </div>
                          ))
                        ) : (
                          <span className="text-xs font-bold text-slate-300 italic">N/A</span>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-5">
                      {clientLots.length > 0 ? (
                        <div className="flex items-center gap-2">
                          <div className={`w-2 h-2 rounded-full ${hasOverdue ? 'bg-red-500' : 'bg-emerald-500'}`}></div>
                          <span className="text-xs font-bold text-slate-700">Al día</span>
                        </div>
                      ) : (
                        <span className="text-xs font-bold text-slate-400">Prospecto</span>
                      )}
                    </td>
                    <td className="px-6 py-5 text-right">
                      <div className="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button className="p-2 hover:bg-emerald-50 text-emerald-600 rounded-lg transition-colors" title="Ver ficha">
                          <Eye className="w-4 h-4" />
                        </button>
                        <button className="p-2 hover:bg-slate-100 text-slate-400 rounded-lg transition-colors">
                          <MoreHorizontal className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
          {filteredClients.length === 0 && (
            <div className="p-20 text-center flex flex-col items-center justify-center bg-slate-50/50">
              <div className="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 text-slate-300 shadow-sm">
                <Users className="w-8 h-8" />
              </div>
              <h4 className="text-slate-800 font-black uppercase tracking-tight">Sin Coincidencias</h4>
              <p className="text-slate-400 text-sm max-w-[250px] mx-auto mt-2">No se encontraron clientes que coincidan con los criterios de búsqueda.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default ClientsView;
