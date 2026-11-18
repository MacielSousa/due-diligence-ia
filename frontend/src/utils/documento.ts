/**
 * Validação de CPF/CNPJ no navegador (mesmo algoritmo do backend em
 * app/Domain/DueDiligence/Documento.php). Serve para avisar o usuário ANTES de enviar;
 * a validação que vale continua sendo a da API.
 */
import type { TipoTerceiro } from '@/types/terceiro'

export function somenteDigitos(valor: string): string {
  return valor.replace(/\D/g, '')
}

function todosIguais(digitos: string): boolean {
  return /^(\d)\1+$/.test(digitos)
}

export function cpfValido(valor: string): boolean {
  const cpf = somenteDigitos(valor)
  if (cpf.length !== 11 || todosIguais(cpf)) return false

  for (let posicao = 9; posicao < 11; posicao++) {
    let soma = 0
    for (let i = 0; i < posicao; i++) soma += Number(cpf[i]) * (posicao + 1 - i)
    const resto = (soma * 10) % 11
    if (Number(cpf[posicao]) !== (resto === 10 ? 0 : resto)) return false
  }
  return true
}

export function cnpjValido(valor: string): boolean {
  const cnpj = somenteDigitos(valor)
  if (cnpj.length !== 14 || todosIguais(cnpj)) return false

  const pesos: [number, number[]][] = [
    [12, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]],
    [13, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]],
  ]
  for (const [posicao, lista] of pesos) {
    const soma = lista.reduce((total, peso, i) => total + Number(cnpj[i]) * peso, 0)
    const resto = soma % 11
    if (Number(cnpj[posicao]) !== (resto < 2 ? 0 : 11 - resto)) return false
  }
  return true
}

export function documentoValido(tipo: TipoTerceiro, valor: string): boolean {
  return tipo === 'pj' ? cnpjValido(valor) : cpfValido(valor)
}
