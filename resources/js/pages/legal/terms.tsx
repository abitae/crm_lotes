import { Link } from '@inertiajs/react';
import LegalDocumentLayout from '@/layouts/legal-document-layout';
import { publisherContact } from '@/legal/publisher-contact';
import legal from '@/routes/legal';

const sectionTitle = 'mt-10 text-lg font-semibold text-slate-900 first:mt-0 dark:text-white';
const paragraph = 'mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300';
const list = 'mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-700 dark:text-slate-300';

export default function LegalTerms() {
    return (
        <LegalDocumentLayout title="Términos y condiciones de uso" headTitle="Términos y condiciones">
            <div className="space-y-2">
                <p className={paragraph}>
                    Al acceder o utilizar las aplicaciones web y servicios asociados a esta plataforma (en adelante, la
                    «aplicación»), usted acepta quedar vinculado por los presentes términos y condiciones. Si no está de
                    acuerdo, debe abstenerse de utilizar la aplicación.
                </p>

                <h2 className={sectionTitle}>1. Objeto</h2>
                <p className={paragraph}>
                    La aplicación está destinada al uso corporativo o profesional autorizado por la organización que la
                    administra. Facilita la gestión operativa (por ejemplo, proyectos, lotes, clientes, comisiones y
                    flujos internos) según los módulos habilitados para su cuenta.
                </p>

                <h2 className={sectionTitle}>2. Cuentas y acceso</h2>
                <ul className={list}>
                    <li>
                        El acceso se otorga mediante credenciales personales o mecanismos de autenticación aprobados por
                        el administrador.
                    </li>
                    <li>Es responsabilidad del usuario mantener la confidencialidad de sus credenciales.</li>
                    <li>
                        La organización puede suspender o revocar el acceso ante incumplimientos, riesgos de seguridad o
                        finalización de la relación laboral o contractual.
                    </li>
                </ul>

                <h2 className={sectionTitle}>3. Uso permitido</h2>
                <p className={paragraph}>El usuario se compromete a:</p>
                <ul className={list}>
                    <li>Utilizar la aplicación únicamente para fines lícitos y alineados con las políticas internas.</li>
                    <li>No intentar vulnerar la seguridad, extraer datos de forma masiva no autorizada ni interferir con el servicio.</li>
                    <li>No compartir su sesión con terceros no autorizados.</li>
                    <li>Registrar información veraz cuando la aplicación lo requiera para fines operativos o legales.</li>
                </ul>

                <h2 className={sectionTitle}>4. Propiedad intelectual y datos</h2>
                <p className={paragraph}>
                    Los elementos de software, diseño, marcas y documentación asociados a la aplicación son titularidad
                    de sus respectivos propietarios. Los datos introducidos por usuarios autorizados pertenecen a la
                    organización responsable del tratamiento, salvo pacto distinto por escrito.
                </p>

                <h2 className={sectionTitle}>5. Disponibilidad y cambios</h2>
                <p className={paragraph}>
                    Se procurará la continuidad del servicio, sin garantía de disponibilidad ininterrumpida. Las
                    funcionalidades pueden actualizarse, modificarse o retirarse con el fin de mejorar la seguridad, el
                    cumplimiento normativo o la operativa del negocio.
                </p>

                <h2 className={sectionTitle}>6. Responsabilidad</h2>
                <p className={paragraph}>
                    La aplicación se ofrece en el marco del acuerdo entre la organización y sus proveedores o
                    administradores técnicos. En la medida permitida por la ley aplicable, no se asume responsabilidad por
                    daños indirectos, pérdida de beneficios o interrupciones derivadas de un uso indebido, de causas
                    ajenas al control razonable o de decisiones tomadas con base en la información consignada en el
                    sistema.
                </p>

                <h2 className={sectionTitle}>7. Legislación aplicable</h2>
                <p className={paragraph}>
                    Para cualquier controversia se aplicará la legislación que corresponda según el domicilio de la
                    organización titular del despliegue, sin perjuicio de normas imperativas que resulten aplicables.
                </p>

                <h2 className={sectionTitle}>8. Contacto del editor</h2>
                <p className={paragraph}>
                    Para consultas sobre estos términos, el uso de la aplicación o el servicio, puede contactar al
                    editor:
                </p>
                <ul className={list}>
                    <li>
                        <span className="font-medium text-slate-800 dark:text-slate-200">Titular / editor:</span>{' '}
                        {publisherContact.fullName}
                    </li>
                    <li>
                        <span className="font-medium text-slate-800 dark:text-slate-200">Correo electrónico:</span>{' '}
                        <a
                            href={`mailto:${publisherContact.email}`}
                            className="font-medium text-emerald-600 underline-offset-2 hover:underline dark:text-emerald-400"
                        >
                            {publisherContact.email}
                        </a>
                    </li>
                    <li>
                        <span className="font-medium text-slate-800 dark:text-slate-200">Domicilio:</span>{' '}
                        {publisherContact.physicalAddress}
                    </li>
                </ul>
                <p className={paragraph}>
                    Los usuarios corporativos pueden además dirigirse al responsable de sistemas o al área legal de su
                    organización para asuntos internos.
                </p>

                <p className={`${paragraph} mt-8 border-t border-slate-200 pt-8 dark:border-slate-800`}>
                    También puede consultar nuestra{' '}
                    <Link
                        href={legal.privacy()}
                        className="font-medium text-emerald-600 underline-offset-2 hover:underline dark:text-emerald-400"
                    >
                        política de privacidad
                    </Link>
                    .
                </p>
            </div>
        </LegalDocumentLayout>
    );
}
